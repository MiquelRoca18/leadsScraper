<?php

namespace Tests\Feature;

use App\Services\MapLeadsApiService;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class UpstreamStateUxTest extends TestCase
{
    public function test_home_shows_real_empty_state_when_upstream_returns_empty_data(): void
    {
        $emptyMock = Mockery::mock(MapLeadsApiService::class);
        $emptyMock->shouldReceive('getJobs')->once()->with(3)->andReturn([]);
        $emptyMock->shouldReceive('getProxyStatus')->once()->andReturn([]);
        $this->app->instance(MapLeadsApiService::class, $emptyMock);

        $this->get('/')
            ->assertOk()
            ->assertSee('Aún no has hecho ninguna extracción.')
            ->assertDontSee('No se pudo cargar la actividad reciente');
    }

    public function test_home_shows_error_state_and_recovery_cta_when_upstream_fails(): void
    {
        $errorMock = Mockery::mock(MapLeadsApiService::class);
        $errorMock->shouldReceive('getJobs')->once()->with(3)->andThrow(new RuntimeException('backend down'));
        $errorMock->shouldReceive('getProxyStatus')->once()->andThrow(new RuntimeException('backend down'));
        $this->app->instance(MapLeadsApiService::class, $errorMock);

        $this->get('/')
            ->assertOk()
            ->assertSee('No se pudo cargar la actividad reciente')
            ->assertSee('Reintentar');
    }

    public function test_history_shows_recovery_cta_when_upstream_fails(): void
    {
        $mock = Mockery::mock(MapLeadsApiService::class);
        $mock->shouldReceive('getJobs')->once()->with(200)->andThrow(new RuntimeException('backend down'));
        $mock->shouldReceive('getProxyStatus')->once()->andReturn([]);
        $this->app->instance(MapLeadsApiService::class, $mock);

        $this->get('/history')
            ->assertOk()
            ->assertSee('No se pudo cargar el historial')
            ->assertSee('Reintentar');
    }

    public function test_search_page_keeps_rendering_and_exposes_backend_warning(): void
    {
        $mock = Mockery::mock(MapLeadsApiService::class);
        $mock->shouldReceive('getProxyStatus')->once()->andThrow(new RuntimeException('backend down'));
        $this->app->instance(MapLeadsApiService::class, $mock);

        $this->get('/search')
            ->assertOk()
            ->assertSee('No se pudo obtener el estado del backend')
            ->assertSee('Reintentar estado');
    }

    public function test_leads_page_renders_error_banner_instead_of_silent_empty_when_upstream_fails(): void
    {
        $mock = Mockery::mock(MapLeadsApiService::class);
        $mock->shouldReceive('getJob')->once()->with('job-1')->andThrow(new RuntimeException('backend down'));
        $mock->shouldReceive('getLeads')->once()->with('job-1', 500)->andThrow(new RuntimeException('backend down'));
        $mock->shouldReceive('getProxyStatus')->once()->andReturn([]);
        $this->app->instance(MapLeadsApiService::class, $mock);

        $this->get('/leads?job_id=job-1')
            ->assertOk()
            ->assertSee('No se pudieron cargar los leads por un error del backend')
            ->assertSee('No se mostrará un vacío silencioso');
    }
}
