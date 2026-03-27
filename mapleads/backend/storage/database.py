import logging
import os
from datetime import datetime, timezone

import aiosqlite

from backend.config.settings import settings

logger = logging.getLogger(__name__)

_CREATE_LEADS = """
CREATE TABLE IF NOT EXISTS leads (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id        TEXT,
    place_id      TEXT UNIQUE,
    business_name TEXT,
    address       TEXT,
    phone         TEXT,
    website       TEXT,
    email         TEXT,
    email_status  TEXT DEFAULT 'pending',
    category      TEXT,
    rating        REAL,
    maps_url      TEXT,
    scraped_at    DATETIME DEFAULT CURRENT_TIMESTAMP
)
"""

_CREATE_SCRAPE_JOBS = """
CREATE TABLE IF NOT EXISTS scrape_jobs (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id       TEXT UNIQUE,
    query        TEXT,
    location     TEXT,
    status       TEXT DEFAULT 'running',
    progress     INTEGER DEFAULT 0,
    total        INTEGER DEFAULT 0,
    emails_found INTEGER DEFAULT 0,
    started_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    finished_at  DATETIME
)
"""


def _db_path() -> str:
    path = settings.db_path
    os.makedirs(os.path.dirname(os.path.abspath(path)), exist_ok=True)
    return path


async def init_db() -> None:
    """Create tables if they don't exist."""
    async with aiosqlite.connect(_db_path()) as db:
        await db.execute(_CREATE_LEADS)
        await db.execute(_CREATE_SCRAPE_JOBS)
        await db.commit()
    logger.info("Database initialized at %s", _db_path())


async def create_job(job_id: str, query: str, location: str, total: int) -> None:
    async with aiosqlite.connect(_db_path()) as db:
        await db.execute(
            "INSERT INTO scrape_jobs (job_id, query, location, total) VALUES (?, ?, ?, ?)",
            (job_id, query, location, total),
        )
        await db.commit()


async def get_job(job_id: str) -> dict | None:
    async with aiosqlite.connect(_db_path()) as db:
        db.row_factory = aiosqlite.Row
        async with db.execute(
            "SELECT * FROM scrape_jobs WHERE job_id = ?", (job_id,)
        ) as cursor:
            row = await cursor.fetchone()
            return dict(row) if row else None


async def update_job_total(job_id: str, total: int) -> None:
    async with aiosqlite.connect(_db_path()) as db:
        await db.execute(
            "UPDATE scrape_jobs SET total = ? WHERE job_id = ?",
            (total, job_id),
        )
        await db.commit()


async def update_job_progress(job_id: str, progress: int, emails_found: int) -> None:
    async with aiosqlite.connect(_db_path()) as db:
        await db.execute(
            "UPDATE scrape_jobs SET progress = ?, emails_found = ? WHERE job_id = ?",
            (progress, emails_found, job_id),
        )
        await db.commit()


async def finish_job(job_id: str, status: str) -> None:
    async with aiosqlite.connect(_db_path()) as db:
        await db.execute(
            "UPDATE scrape_jobs SET status = ?, finished_at = ? WHERE job_id = ?",
            (status, datetime.now(timezone.utc).isoformat(), job_id),
        )
        await db.commit()


async def save_lead(lead: dict, job_id: str) -> None:
    """
    Insert a lead. Silently ignores duplicate place_id (UNIQUE constraint).
    Updates email/email_status if the record already exists without them.
    """
    async with aiosqlite.connect(_db_path()) as db:
        await db.execute(
            """
            INSERT INTO leads
                (job_id, place_id, business_name, address, phone, website,
                 email, email_status, category, rating, maps_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT(place_id) DO UPDATE SET
                email        = COALESCE(excluded.email, leads.email),
                email_status = COALESCE(excluded.email_status, leads.email_status),
                job_id       = excluded.job_id
            """,
            (
                job_id,
                lead.get("place_id"),
                lead.get("business_name"),
                lead.get("address"),
                lead.get("phone"),
                lead.get("website"),
                lead.get("email"),
                lead.get("email_status", "pending"),
                lead.get("category"),
                lead.get("rating"),
                lead.get("maps_url"),
            ),
        )
        await db.commit()


async def get_all_jobs(limit: int = 100) -> list[dict]:
    """Return all scraping jobs ordered by most recent first."""
    async with aiosqlite.connect(_db_path()) as db:
        db.row_factory = aiosqlite.Row
        async with db.execute(
            "SELECT * FROM scrape_jobs ORDER BY started_at DESC LIMIT ?", (limit,)
        ) as cursor:
            rows = await cursor.fetchall()
            return [dict(row) for row in rows]


async def get_leads_stats() -> dict:
    """Return lead counts grouped by source (for databases page)."""
    async with aiosqlite.connect(_db_path()) as db:
        async with db.execute("SELECT COUNT(*) FROM leads") as cursor:
            row = await cursor.fetchone()
            total = row[0] if row else 0
    return {"google_maps": total, "instagram": 0}


async def get_leads(job_id: str | None = None, has_email: bool | None = None) -> list[dict]:
    conditions = []
    params: list = []

    if job_id:
        conditions.append("job_id = ?")
        params.append(job_id)
    if has_email is True:
        conditions.append("email IS NOT NULL AND email != ''")
    elif has_email is False:
        conditions.append("(email IS NULL OR email = '')")

    where = f"WHERE {' AND '.join(conditions)}" if conditions else ""
    query = f"SELECT * FROM leads {where} ORDER BY scraped_at DESC"

    async with aiosqlite.connect(_db_path()) as db:
        db.row_factory = aiosqlite.Row
        async with db.execute(query, params) as cursor:
            rows = await cursor.fetchall()
            return [dict(row) for row in rows]


async def delete_lead(lead_id: int) -> bool:
    async with aiosqlite.connect(_db_path()) as db:
        cursor = await db.execute("DELETE FROM leads WHERE id = ?", (lead_id,))
        await db.commit()
        return cursor.rowcount > 0
