<?php

namespace Tests\Unit;

use App\Services\MapLeadsApiService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MapLeadsApiServiceTest extends TestCase
{
    public function test_characterization_get_jobs_returns_upstream_payload_when_successful(): void
    {
        config(['services.mapleads.api_url' => 'http://upstream.test']);

        Http::fake([
            'http://upstream.test/api/jobs*' => Http::response([['job_id' => 'abc']], 200),
        ]);

        $service = app(MapLeadsApiService::class);
        $jobs = $service->getJobs(1);

        $this->assertCount(1, $jobs);
        $this->assertSame('abc', $jobs[0]['job_id']);
    }

    public function test_get_jobs_throws_on_upstream_error_instead_of_silencing_as_empty_array(): void
    {
        config(['services.mapleads.api_url' => 'http://upstream.test']);

        Http::fake([
            'http://upstream.test/api/jobs*' => Http::response(['message' => 'fail'], 500),
        ]);

        $this->expectException(RequestException::class);

        $service = app(MapLeadsApiService::class);
        $service->getJobs(10);
    }

    public function test_service_throws_when_mapleads_api_url_is_not_configured(): void
    {
        config(['services.mapleads.api_url' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MAPLEADS_API_URL is not configured.');

        app(MapLeadsApiService::class);
    }

    public function test_get_jobs_can_return_empty_array_when_upstream_response_is_valid_empty_list(): void
    {
        config(['services.mapleads.api_url' => 'http://upstream.test']);

        Http::fake([
            'http://upstream.test/api/jobs*' => Http::response([], 200),
        ]);

        $service = app(MapLeadsApiService::class);
        $jobs = $service->getJobs(25);

        $this->assertSame([], $jobs);
    }
}
