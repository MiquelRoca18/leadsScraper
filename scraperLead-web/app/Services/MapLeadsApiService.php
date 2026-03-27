<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MapLeadsApiService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.mapleads.api_url'), '/');
        throw_if(blank($this->baseUrl), new RuntimeException('MAPLEADS_API_URL is not configured.'));
    }

    public function getJobs(int $limit = 100): array
    {
        return $this->getJson('/api/jobs', ['limit' => $limit], 10);
    }

    public function getJob(string $jobId): array
    {
        return $this->getJson("/api/jobs/{$jobId}", [], 10);
    }

    public function getLeads(?string $jobId = null): array
    {
        $params = $jobId ? ['job_id' => $jobId] : [];

        return $this->getJson('/api/leads', $params, 15);
    }

    public function getStats(): array
    {
        return $this->getJson('/api/stats', [], 10);
    }

    public function getProxyStatus(): array
    {
        return $this->getJson('/api/proxy/status', [], 5);
    }

    private function getJson(string $path, array $query = [], int $timeout = 10): array
    {
        $response = Http::timeout($timeout)
            ->acceptJson()
            ->get("{$this->baseUrl}{$path}", $query)
            ->throw();

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException("Upstream response for [{$path}] is not a valid JSON array/object.");
        }

        return $payload;
    }
}
