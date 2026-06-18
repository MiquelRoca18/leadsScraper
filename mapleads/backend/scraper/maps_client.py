import asyncio
import logging
import math
import random

import curl_cffi.requests as curl_requests

from backend.config.settings import settings
from backend.proxy.proxy_manager import proxy_manager
from backend.scraper.maps_parser import (
    extract_preview_url_from_html,
    hex_cid_to_decimal,
    parse_cids_from_maps_response,
    parse_maps_response,
    parse_place_from_html,
    parse_place_from_preview_json,
)

logger = logging.getLogger(__name__)

_SEARCH_URL = "https://www.google.com/search"
_MAPS_PLACE_URL = "https://www.google.com/maps"

_SEARCH_HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/131.0.0.0 Safari/537.36"
    ),
    "Accept-Language": "es-ES,es;q=0.9",
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
}

_PLACE_HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/131.0.0.0 Safari/537.36"
    ),
    "Accept-Language": "es-ES,es;q=0.9",
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
    "Referer": "https://www.google.com/",
}

# Bypass Google GDPR consent page ("Antes de ir a Google Maps").
# Without these cookies, Google redirects place page requests to the consent
# screen and returns an HTML page whose <title> is "Antes de ir a Google Maps".
_GOOGLE_CONSENT_COOKIES = {
    "CONSENT": "YES+cb.20210720-07-p0.en+FX+410",
    "SOCS": "CAESHAgBEhJnd3NfMjAyNDA5MTAtMF9SQzIaAnplIAEaBgiA3pO1Bg",
}


def _radius_to_zoom(radius_km: float) -> int:
    """Convert search radius in km to an approximate Google Maps zoom level."""
    return max(9, round(15 - math.log2(max(1.0, radius_km))))


_MAPS_PREVIEW_URL = "https://www.google.com/maps/preview/place"

_PREVIEW_HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/131.0.0.0 Safari/537.36"
    ),
    "Accept-Language": "es-ES,es;q=0.9",
    "Accept": "*/*",
    "Referer": "https://www.google.com/maps/",
}


class MapsFetchError(RuntimeError):
    """Error operativo (proxy/red/bloqueo) al consultar Google Maps."""

    def __init__(self, message: str, *, kind: str = "unknown", retryable: bool = True) -> None:
        super().__init__(message)
        self.kind = kind
        self.retryable = retryable


async def _fetch_place_details(hex_cid: str) -> dict | None:
    """
    Fetch full business details for a single place by its hex CID.

    Two-step process:
      1. Fetch the Google Maps place HTML page (/maps?cid={decimal}).
         The response embeds a <link> element pointing to the JSON preview API
         with the exact business coordinates embedded in the pb parameter.
      2. Fetch the preview/place JSON endpoint → full structured data
         (name, address, phone, website, rating, category).

    Falls back to title extraction from the HTML if the preview step fails.
    Returns a business dict or None on failure.
    """
    decimal = hex_cid_to_decimal(hex_cid)
    if not decimal:
        logger.debug("_fetch_place_details: invalid hex_cid %s", hex_cid)
        return None

    proxy = await proxy_manager.wait_for_available()
    if proxy is None and proxy_manager._stats and not proxy_manager.is_bandwidth_exhausted:
        logger.warning("_fetch_place_details: no proxy available for cid=%s", hex_cid)
        return None
    if proxy is None and proxy_manager.is_bandwidth_exhausted:
        logger.info("_fetch_place_details: all proxies bandwidth-exhausted — using direct connection")

    # --- Step 1: Fetch place page HTML to get the preview URL ---
    # On 402/ProxyError, retry immediately with direct connection (same as _fetch_cid_list)
    proxies_to_try: list[str | None] = [proxy]
    html: str | None = None
    html_response = None

    for current_proxy in proxies_to_try:
        proxies = {"https": current_proxy, "http": current_proxy} if current_proxy else None
        try:
            loop = asyncio.get_running_loop()
            html_response = await loop.run_in_executor(
                None,
                lambda: curl_requests.get(
                    _MAPS_PLACE_URL,
                    params={"cid": decimal, "hl": "es", "gl": "es"},
                    headers=_PLACE_HEADERS,
                    cookies=_GOOGLE_CONSENT_COOKIES,
                    proxies=proxies,
                    impersonate="chrome131",
                    timeout=15,
                    allow_redirects=True,
                ),
            )
            break  # request succeeded; handle status outside the loop
        except Exception as exc:
            logger.debug("_fetch_place_details: HTML fetch error cid=%s: %s", hex_cid, exc)
            if current_proxy and ("CONNECT tunnel failed" in str(exc) or "ProxyError" in type(exc).__name__):
                await proxy_manager.report_bandwidth_exhausted(current_proxy)
                if None not in proxies_to_try:
                    logger.info(
                        "_fetch_place_details: proxy returned 402 — retrying with direct connection"
                    )
                    proxies_to_try.append(None)
                    continue
            elif current_proxy:
                await proxy_manager.report_error(current_proxy)
            return None

    if html_response is None:
        return None

    if html_response.status_code == 429:
        await proxy_manager.report_error(current_proxy)
        return None
    if html_response.status_code != 200:
        logger.debug("_fetch_place_details: HTML status %d for cid=%s",
                     html_response.status_code, hex_cid)
        await proxy_manager.report_error(current_proxy)
        return None
    if "Antes de ir a Google Maps" in html_response.text or "Before you continue" in html_response.text:
        logger.warning("_fetch_place_details: consent page for cid=%s — check cookies", hex_cid)
        return None

    html = html_response.text
    preview_url = extract_preview_url_from_html(html)

    if not preview_url:
        # Last-resort fallback: try to get at least the name from the title
        logger.debug("_fetch_place_details: no preview link for cid=%s, using title fallback", hex_cid)
        return parse_place_from_html(html, hex_cid)

    # --- Step 2: Fetch the preview/place JSON endpoint ---
    # Same proxy→direct fallback as Step 1
    preview_proxy = await proxy_manager.wait_for_available()
    if preview_proxy is None and proxy_manager._stats and not proxy_manager.is_bandwidth_exhausted:
        return None

    step2_proxies_to_try: list[str | None] = [preview_proxy]
    json_response = None

    for step2_proxy in step2_proxies_to_try:
        step2_proxies = {"https": step2_proxy, "http": step2_proxy} if step2_proxy else None
        try:
            json_response = await loop.run_in_executor(
                None,
                lambda: curl_requests.get(
                    preview_url,
                    headers=_PREVIEW_HEADERS,
                    cookies=_GOOGLE_CONSENT_COOKIES,
                    proxies=step2_proxies,
                    impersonate="chrome131",
                    timeout=15,
                ),
            )
            break
        except Exception as exc:
            if step2_proxy and ("CONNECT tunnel failed" in str(exc) or "ProxyError" in type(exc).__name__):
                await proxy_manager.report_bandwidth_exhausted(step2_proxy)
                if None not in step2_proxies_to_try:
                    step2_proxies_to_try.append(None)
                    continue
            logger.debug("_fetch_place_details: preview fetch error cid=%s: %s", hex_cid, exc)
            return parse_place_from_html(html, hex_cid)

    if json_response is None:
        return parse_place_from_html(html, hex_cid)

    if json_response.status_code != 200:
        logger.debug("_fetch_place_details: preview status %d for cid=%s",
                     json_response.status_code, hex_cid)
        return parse_place_from_html(html, hex_cid)

    raw = json_response.text
    if raw.startswith(")]}'"):
        raw = raw[4:].lstrip("\n")

    business = parse_place_from_preview_json(raw, hex_cid)
    if business:
        await proxy_manager.report_success(preview_proxy)
        logger.debug("_fetch_place_details: resolved '%s' via preview JSON", business.get("business_name"))
    else:
        logger.debug("_fetch_place_details: preview JSON parse failed for cid=%s, using title fallback", hex_cid)
        business = parse_place_from_html(html, hex_cid)

    return business


async def _fetch_cid_list(
    query: str,
    location: str,
    start: int = 0,
    lat: float | None = None,
    lng: float | None = None,
    radius_km: float = 10.0,
) -> list[str]:
    """
    Fetch the tbm=map JSON response and extract hex CIDs.

    When lat/lng are provided, adds the ``ll`` parameter to center the Google
    Maps search on the given coordinates with a zoom level derived from radius_km.

    Returns up to 20 hex CID strings. Empty list on error.

    Proxy fallback: if the selected proxy returns a 402 (bandwidth exhausted),
    the request is retried immediately with a direct connection instead of
    propagating the error.  This ensures a single bad proxy never kills the job.
    """
    proxy = await proxy_manager.wait_for_available()
    if proxy is None and proxy_manager._stats and not proxy_manager.is_bandwidth_exhausted:
        logger.warning("_fetch_cid_list: no proxy available, skipping")
        return []
    if proxy is None and proxy_manager.is_bandwidth_exhausted:
        logger.info("_fetch_cid_list: all proxies bandwidth-exhausted — using direct connection")

    # Build query string: combine keyword + location text (if any)
    search_query = f"{query} {location}".strip() if location else query

    params: dict[str, str] = {
        "tbm": "map",
        "hl": "es",
        "gl": "es",
        "q": search_query,
        "num": "20",
        "start": str(start),
    }

    # Pin the map to explicit coordinates when available
    if lat is not None and lng is not None:
        zoom = _radius_to_zoom(radius_km)
        params["ll"] = f"@{lat},{lng},{zoom}z"
        logger.debug(
            "_fetch_cid_list: using coords lat=%.5f lng=%.5f zoom=%d radius=%.1fkm",
            lat, lng, zoom, radius_km,
        )

    # On 402/ProxyError, we add None (direct) to the list and retry immediately
    # instead of propagating the error and waiting for all proxies to fail.
    proxies_to_try: list[str | None] = [proxy]
    last_exc: Exception | None = None

    for current_proxy in proxies_to_try:
        proxies = {"https": current_proxy, "http": current_proxy} if current_proxy else None
        try:
            loop = asyncio.get_running_loop()
            response = await loop.run_in_executor(
                None,
                lambda: curl_requests.get(
                    _SEARCH_URL,
                    params=params,
                    headers=_SEARCH_HEADERS,
                    cookies=_GOOGLE_CONSENT_COOKIES,
                    proxies=proxies,
                    impersonate="chrome131",
                    timeout=20,
                ),
            )

            if response.status_code == 429:
                await proxy_manager.report_error(current_proxy)
                raise MapsFetchError("Maps rate limited (429)", kind="rate_limited", retryable=True)

            if response.status_code != 200:
                logger.warning("_fetch_cid_list: status %d for '%s'", response.status_code, search_query)
                await proxy_manager.report_error(current_proxy)
                raise MapsFetchError(
                    f"Maps unexpected status {response.status_code}",
                    kind="bad_status",
                    retryable=True,
                )

            raw = response.text
            if raw.startswith(")]}'"):
                raw = raw[4:].lstrip("\n")

            # Try old full-detail format first (FORMAT A)
            businesses = parse_maps_response(raw)
            if businesses:
                await proxy_manager.report_success(current_proxy)
                logger.debug("_fetch_cid_list: FORMAT A hit — %d businesses directly", len(businesses))
                # Return a special sentinel so search_maps knows to use these directly
                return [("__FORMAT_A__", businesses)]  # type: ignore[list-item]

            # New CID-only format (FORMAT B)
            cids = parse_cids_from_maps_response(raw)
            if cids:
                await proxy_manager.report_success(current_proxy)
                logger.debug("_fetch_cid_list: FORMAT B — %d CIDs for '%s' start=%d", len(cids), search_query, start)
            else:
                logger.debug("_fetch_cid_list: no data in either format for '%s'", search_query)
                # Puede ser 'sin resultados' real. No lo tratamos como error operativo.

            await asyncio.sleep(random.uniform(settings.request_delay_min, settings.request_delay_max))
            return cids

        except MapsFetchError as exc:
            # Maps-level errors (429, bad status): don't retry with direct connection
            last_exc = exc
            break

        except Exception as exc:
            logger.error("_fetch_cid_list('%s', start=%d): error=%s", search_query, start, exc, exc_info=True)
            if current_proxy and ("CONNECT tunnel failed" in str(exc) or "ProxyError" in type(exc).__name__):
                await proxy_manager.report_bandwidth_exhausted(current_proxy)
                # Immediately retry with direct connection (don't wait for all proxies to fail)
                if None not in proxies_to_try:
                    logger.info(
                        "_fetch_cid_list: proxy returned 402 (bandwidth exhausted) — "
                        "retrying with direct connection"
                    )
                    proxies_to_try.append(None)
                last_exc = MapsFetchError(str(exc), kind="connection", retryable=True)
                continue  # try direct next
            elif current_proxy:
                await proxy_manager.report_error(current_proxy)
            last_exc = MapsFetchError(str(exc), kind="connection", retryable=True)
            break

    if last_exc is not None:
        raise last_exc
    return []


async def search_maps(
    query: str,
    location: str,
    start: int = 0,
    lat: float | None = None,
    lng: float | None = None,
    radius_km: float = 10.0,
) -> list[dict]:
    """
    Return normalized business dicts for a single page of Google Maps results.

    Two-step process:
      1. Fetch CID list from tbm=map (FORMAT B) or full data directly (FORMAT A)
      2. If FORMAT B: fetch place details for each CID in parallel

    Args:
        query: What to search (e.g. "dentistas")
        location: Where to search as text (e.g. "Valencia"), used when no coords
        start: Pagination offset (0, 20, 40…)
        lat: Latitude to center the search map
        lng: Longitude to center the search map
        radius_km: Search radius in km (used for zoom level)

    Returns:
        List of business dicts. Empty list on error or no proxy available.
    """
    cid_list = await _fetch_cid_list(query, location, start=start, lat=lat, lng=lng, radius_km=radius_km)

    if not cid_list:
        return []

    # FORMAT A fast-path: full businesses returned directly
    if len(cid_list) == 1 and isinstance(cid_list[0], tuple) and cid_list[0][0] == "__FORMAT_A__":
        return cid_list[0][1]

    # FORMAT B: fetch details for each CID concurrently
    logger.info("search_maps: fetching details for %d places (FORMAT B)…", len(cid_list))
    semaphore = asyncio.Semaphore(settings.max_concurrent_requests)

    async def fetch_with_semaphore(cid: str) -> dict | None:
        async with semaphore:
            result = await _fetch_place_details(cid)
            await asyncio.sleep(random.uniform(
                settings.request_delay_min * 0.5,
                settings.request_delay_max * 0.5,
            ))
            return result

    tasks = [fetch_with_semaphore(cid) for cid in cid_list]
    results = await asyncio.gather(*tasks)

    businesses = [b for b in results if b is not None and b.get("business_name")]
    logger.debug("search_maps('%s %s', start=%d): %d/%d places resolved",
                 query, location, start, len(businesses), len(cid_list))
    return businesses


async def search_maps_paginated(
    query: str,
    location: str,
    max_results: int = 50,
    lat: float | None = None,
    lng: float | None = None,
    radius_km: float = 10.0,
) -> list[dict]:
    """
    Paginate through Google Maps results up to max_results.

    Stops early if a page returns fewer than 20 results (no more pages).
    """
    all_results: list[dict] = []
    start = 0

    while len(all_results) < max_results:
        batch: list[dict] = []
        last_error: Exception | None = None
        for attempt in range(3):
            try:
                batch = await search_maps(query, location, start=start, lat=lat, lng=lng, radius_km=radius_km)
                last_error = None
                break
            except MapsFetchError as exc:
                last_error = exc
                if not exc.retryable:
                    break
                backoff = 0.6 * (2**attempt) + random.uniform(0.0, 0.25)
                logger.warning(
                    "search_maps_paginated: retry %d/3 after MapsFetchError(kind=%s): %s",
                    attempt + 1,
                    getattr(exc, "kind", "unknown"),
                    exc,
                )
                await asyncio.sleep(backoff)

        if last_error is not None and not batch:
            raise last_error
        if not batch:
            break

        all_results.extend(batch)
        start += 20

        if len(batch) < 20:
            break

    return all_results[:max_results]
