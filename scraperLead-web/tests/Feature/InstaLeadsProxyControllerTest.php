<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstaLeadsProxyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.instaleads.api_url' => 'http://upstream.test']);

        // Instagram scraper opera sin sesiones (fase 2), así que evitamos el CSRF en tests.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_proxy_rejects_instagram_sessions_path(): void
    {
        $this->getJson('/api/instagram/sessions')
            ->assertStatus(404);
    }

    public function test_proxy_forwards_proxy_status_get(): void
    {
        Http::fake([
            'http://upstream.test/api/proxy/status*' => Http::response(['available_now' => 7], 200),
        ]);

        $this->get('/api/instagram/proxy/status')
            ->assertOk()
            ->assertJson(['available_now' => 7]);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'http://upstream.test/api/proxy/status';
        });
    }

    public function test_proxy_forwards_instagram_search_post_payload_contract(): void
    {
        Http::fake([
            'http://upstream.test/api/instagram/search*' => Http::response(['job_id' => 'abc123', 'status' => 'running'], 200),
        ]);

        $body = '{"mode":"followers","target":"someuser","max_results":2,"email_goal":null}';

        $this->call(
            'POST',
            '/api/instagram/search',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        )->assertOk()
            ->assertJson(['job_id' => 'abc123', 'status' => 'running']);

        Http::assertSent(function ($request) use ($body) {
            return $request->method() === 'POST'
                && $request->url() === 'http://upstream.test/api/instagram/search'
                && $request->body() === $body;
        });
    }
}

