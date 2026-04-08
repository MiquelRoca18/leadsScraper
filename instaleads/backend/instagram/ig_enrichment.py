"""Email enrichment via website scraping.

Cascade strategy for a given bio_url:
  1. Fetch main page → regex email extraction
  2. Parse structured data (JSON-LD, mailto: links, meta tags)
  3. Follow common contact sub-pages (/contact, /contacto, /about, /sobre-mi)
  4. Retry once on transient fetch failure

Rate limit: separate rolling-hour window (external sites, not Instagram quota).
"""

import asyncio
import json
import logging
import re
import time
from collections import deque
from urllib.parse import urljoin, urlparse

import httpx

from backend.config.settings import settings

logger = logging.getLogger(__name__)

EMAIL_RE = re.compile(r"([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})")
BLOCKED_LOCAL_PARTS = {"no-reply", "noreply", "donotreply"}
BLOCKED_DOMAINS = {"example.com", "test.com", "invalid", "localhost"}

# Contact sub-pages to check when the main page has no email
_CONTACT_PATHS = ["/contact", "/contacto", "/about", "/sobre-mi", "/about-us"]


class EnrichmentLimiter:
    """Simple rolling-hour limiter for website enrichment fetches."""

    def __init__(self, max_per_hour: int) -> None:
        self._max = max_per_hour
        self._lock = asyncio.Lock()
        self._timestamps: deque[float] = deque()

    async def allow(self) -> bool:
        async with self._lock:
            now = time.monotonic()
            cutoff = now - 3600
            while self._timestamps and self._timestamps[0] <= cutoff:
                self._timestamps.popleft()
            if len(self._timestamps) >= self._max:
                return False
            self._timestamps.append(now)
            return True


_limiter = EnrichmentLimiter(settings.enrichment_max_fetches_per_hour)

_HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/131.0.0.0 Safari/537.36"
    ),
    "Accept": "text/html,application/xhtml+xml",
}


def _normalize_url(url: str | None) -> str | None:
    if not url:
        return None
    raw = str(url).strip()
    if not raw:
        return None
    if not raw.startswith(("http://", "https://")):
        raw = f"https://{raw}"
    parsed = urlparse(raw)
    if parsed.scheme not in {"http", "https"}:
        return None
    if not parsed.netloc:
        return None
    return raw


def _normalize_email(value: str) -> str | None:
    email = value.strip().strip(".,;:!?()[]{}<>").lower()
    if email.startswith("mailto:"):
        email = email.replace("mailto:", "", 1)
    if "@" not in email:
        return None
    local, _, domain = email.partition("@")
    if not local or not domain:
        return None
    if local in BLOCKED_LOCAL_PARTS:
        return None
    if domain in BLOCKED_DOMAINS:
        return None
    return email


def extract_emails_from_text(text: str) -> list[str]:
    found: list[str] = []
    seen: set[str] = set()
    for match in EMAIL_RE.findall(text or ""):
        normalized = _normalize_email(match)
        if not normalized or normalized in seen:
            continue
        seen.add(normalized)
        found.append(normalized)
    return found


def _extract_from_structured_data(html: str) -> list[str]:
    """Extract emails from structured data in HTML.

    Looks for:
    - <a href="mailto:..."> links
    - <script type="application/ld+json"> JSON-LD blocks
    - <meta> tags with email content
    """
    emails: list[str] = []

    # mailto: links
    for match in re.findall(r'href=["\']mailto:([^"\'?\s]+)', html, re.IGNORECASE):
        normalized = _normalize_email(match)
        if normalized and normalized not in emails:
            emails.append(normalized)

    # JSON-LD blocks
    for block in re.findall(
        r'<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>',
        html,
        re.DOTALL | re.IGNORECASE,
    ):
        try:
            data = json.loads(block)
            # JSON-LD can be a single object or a list
            items = data if isinstance(data, list) else [data]
            for item in items:
                for field in ("email", "contactEmail", "contactPoint"):
                    value = item.get(field)
                    if isinstance(value, str):
                        normalized = _normalize_email(value)
                        if normalized and normalized not in emails:
                            emails.append(normalized)
                    elif isinstance(value, dict):
                        for sub_field in ("email",):
                            sub_value = value.get(sub_field, "")
                            if isinstance(sub_value, str):
                                normalized = _normalize_email(sub_value)
                                if normalized and normalized not in emails:
                                    emails.append(normalized)
        except Exception:
            pass

    # meta tags (og:email, contact:email)
    for match in re.findall(
        r'<meta[^>]+(?:name|property)=["\'](?:og:email|contact:email)["\'][^>]+content=["\']([^"\']+)["\']',
        html,
        re.IGNORECASE,
    ):
        normalized = _normalize_email(match)
        if normalized and normalized not in emails:
            emails.append(normalized)

    return emails


async def _fetch_page(client: httpx.AsyncClient, url: str) -> str | None:
    """Fetch a page with one retry on failure. Returns HTML text or None."""
    for attempt in range(2):
        try:
            resp = await client.get(url, headers=_HEADERS)
            if resp.status_code < 400:
                return resp.text
            return None
        except Exception:
            if attempt == 0:
                await asyncio.sleep(2)
    return None


async def _try_contact_pages(base_url: str, client: httpx.AsyncClient) -> str | None:
    """Check common contact sub-pages for emails. Returns first found email."""
    max_pages = getattr(settings, "enrichment_max_subpages", 3)
    follow = getattr(settings, "enrichment_follow_contact_pages", True)
    if not follow:
        return None

    for path in _CONTACT_PATHS[:max_pages]:
        sub_url = urljoin(base_url, path)
        if not await _limiter.allow():
            break
        html = await _fetch_page(client, sub_url)
        if not html:
            continue
        # Try structured data first, then regex
        emails = _extract_from_structured_data(html) or extract_emails_from_text(html)
        if emails:
            logger.debug("Found email via contact page %s: %s", sub_url, emails[0])
            return emails[0]
    return None


async def enrich_email_from_bio_url(bio_url: str | None) -> str | None:
    """Extract email from a profile's bio URL using a multi-step cascade:

    1. Fetch main page → regex
    2. Parse structured data (JSON-LD, mailto:, meta)
    3. Follow contact sub-pages (if enabled)
    """
    url = _normalize_url(bio_url)
    if not url:
        return None
    if not await _limiter.allow():
        return None

    timeout = httpx.Timeout(settings.enrichment_http_timeout_sec)
    async with httpx.AsyncClient(timeout=timeout, follow_redirects=True, max_redirects=5) as client:
        html = await _fetch_page(client, url)
        if not html:
            return None

        # Step 1: regex on full page text
        candidates = extract_emails_from_text(html)
        if candidates:
            return candidates[0]

        # Step 2: structured data (JSON-LD, mailto:, meta)
        structured = _extract_from_structured_data(html)
        if structured:
            return structured[0]

        # Step 3: follow contact sub-pages
        return await _try_contact_pages(url, client)
