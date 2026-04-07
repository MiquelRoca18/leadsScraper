<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiProxyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mapleads.api_url' => 'http://upstream.test']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_characterization_proxy_forwards_get_requests_to_upstream(): void
    {
        Http::fake([
            'http://upstream.test/api/proxy/status*' => Http::response(['ok' => true], 200),
        ]);

        $this->get('/api/proxy/status?source=characterization')
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'http://upstream.test/api/proxy/status?source=characterization';
        });
    }

    public function test_characterization_proxy_forwards_post_requests_with_raw_payload(): void
    {
        Http::fake([
            'http://upstream.test/api/search' => Http::response(['received' => true], 200),
        ]);

        $body = '{"query":"coffee","limit":5}';

        $this->call(
            'POST',
            '/api/search',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        )
            ->assertOk()
            ->assertJson(['received' => true]);

        Http::assertSent(function ($request) use ($body) {
            return $request->method() === 'POST'
                && $request->url() === 'http://upstream.test/api/search'
                && $request->body() === $body;
        });
    }

    public function test_characterization_proxy_forwards_geo_search_payload_contract(): void
    {
        Http::fake([
            'http://upstream.test/api/search' => Http::response(['received' => true], 200),
        ]);

        $body = '{"query":"dentistas","location":"Valencia","lat":39.4699,"lng":-0.3763,"radius_km":10,"max_results":25}';

        $this->call(
            'POST',
            '/api/search',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        )
            ->assertOk()
            ->assertJson(['received' => true]);

        Http::assertSent(function ($request) use ($body) {
            return $request->method() === 'POST'
                && $request->url() === 'http://upstream.test/api/search'
                && $request->body() === $body;
        });
    }

    public function test_proxy_rejects_disallowed_http_method(): void
    {
        $this->putJson('/api/search', ['query' => 'coffee'])
            ->assertStatus(405);
    }

    public function test_proxy_rejects_disallowed_path(): void
    {
        $this->getJson('/api/not-allowed')
            ->assertStatus(404);
    }

    public function test_proxy_rejects_disallowed_header(): void
    {
        $this->withHeaders([
            'X-Debug-Token' => 'secret',
        ])->get('/api/proxy/status')
            ->assertStatus(400)
            ->assertJson([
                'message' => 'Header not allowed: x-debug-token',
            ]);
    }

    public function test_proxy_allows_cookie_header_without_rejecting(): void
    {
        Http::fake([
            'http://upstream.test/api/search' => Http::response(['received' => true], 200),
        ]);

        $body = '{"query":"dentistas","location":"Albacete","max_results":10}';

        $this->withHeaders(['Cookie' => 'sessionid=abc123'])
            ->call(
                'POST',
                '/api/search',
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                $body
            )
            ->assertOk()
            ->assertJson(['received' => true]);

        Http::assertSent(function ($request) use ($body) {
            return $request->method() === 'POST'
                && $request->url() === 'http://upstream.test/api/search'
                && $request->body() === $body;
        });
    }

    public function test_proxy_allows_jobs_path_with_parameter(): void
    {
        Http::fake([
            'http://upstream.test/api/jobs/*' => Http::response(['ok' => true], 200),
        ]);

        $this->getJson('/api/jobs/job-123')
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'http://upstream.test/api/jobs/job-123';
        });
    }

    public function test_is_allowed_path_matches_jobs_parameter(): void
    {
        $controller = new \App\Http\Controllers\ApiProxyController();
        $method = new \ReflectionMethod(\App\Http\Controllers\ApiProxyController::class, 'isAllowedPath');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, 'jobs/job-123'));
        $this->assertFalse($method->invoke($controller, 'jobs/'));
    }

    public function test_proxy_returns_consistent_502_on_upstream_timeout_or_exception(): void
    {
        Http::fake(function () {
            throw new \RuntimeException('timed out');
        });

        $this->getJson('/api/proxy/status')
            ->assertStatus(502)
            ->assertJson([
                'message' => 'Upstream API unavailable',
            ]);
    }

    public function test_proxy_returns_consistent_502_on_non_json_upstream_error(): void
    {
        Http::fake([
            'http://upstream.test/api/proxy/status*' => Http::response('upstream exploded', 500, ['Content-Type' => 'text/plain']),
        ]);

        $this->get('/api/proxy/status')
            ->assertStatus(502)
            ->assertJson([
                'message' => 'Upstream API unavailable',
            ]);
    }

    public function test_proxy_forwards_query_string_for_allowed_get_path(): void
    {
        Http::fake([
            'http://upstream.test/api/leads*' => Http::response(['ok' => true], 200),
        ]);

        $this->getJson('/api/leads?job_id=job-123&limit=50')
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'http://upstream.test/api/leads?job_id=job-123&limit=50';
        });
    }
}
