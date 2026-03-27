import pytest
from httpx import ASGITransport, AsyncClient

from backend.main import app


@pytest.fixture
async def client():
    async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as c:
        yield c


@pytest.mark.asyncio
async def test_health_endpoint(client):
    res = await client.get("/api/health")
    assert res.status_code == 200
    assert res.json() == {"status": "ok"}


@pytest.mark.asyncio
async def test_search_returns_job_id(client):
    res = await client.post(
        "/api/search",
        json={"query": "test", "location": "Madrid", "max_results": 1},
    )
    assert res.status_code == 200
    data = res.json()
    assert "job_id" in data
    assert data["status"] == "running"


@pytest.mark.asyncio
async def test_get_job_not_found(client):
    res = await client.get("/api/jobs/nonexistent-job-id")
    assert res.status_code == 404


@pytest.mark.asyncio
async def test_get_leads_empty(client):
    res = await client.get("/api/leads?job_id=nonexistent")
    assert res.status_code == 200
    assert res.json() == []


@pytest.mark.asyncio
async def test_export_not_found(client):
    res = await client.get("/api/export/nonexistent")
    assert res.status_code == 404


@pytest.mark.asyncio
async def test_delete_lead_not_found(client):
    res = await client.delete("/api/leads/99999")
    assert res.status_code == 404
