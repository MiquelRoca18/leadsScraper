<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_characterization_root_route_returns_success_status(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_red_objective_named_routes_are_pending_for_future_task(): void
    {
        // [ROJO][OBJETIVO] Se documenta el objetivo, pero no se implementa en Task 1.
        $this->markTestIncomplete('ROJO/OBJETIVO: Las rutas con name() se validaran en tareas posteriores.');
    }
}
