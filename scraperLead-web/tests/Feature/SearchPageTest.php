<?php

namespace Tests\Feature;

use Tests\TestCase;

class SearchPageTest extends TestCase
{
    public function test_characterization_search_page_is_reachable(): void
    {
        $this->get('/search')
            ->assertOk()
            ->assertSee('Google Maps');
    }

    public function test_characterization_search_page_documents_geo_search_contract_visible_in_ui(): void
    {
        $this->get('/search')
            ->assertOk()
            ->assertSee('id="radius-slider"', false)
            ->assertSee('max="50"', false)
            ->assertSee('id="location"', false)
            ->assertSee('Radio permitido: 1-50 km');
    }
}
