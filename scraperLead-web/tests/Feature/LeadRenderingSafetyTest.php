<?php

namespace Tests\Feature;

use Tests\TestCase;

class LeadRenderingSafetyTest extends TestCase
{
    public function test_leads_view_avoids_inline_click_handlers_in_critical_flows(): void
    {
        $content = file_get_contents(resource_path('views/leads.blade.php'));

        $this->assertNotFalse($content);
        $this->assertStringNotContainsString('onclick=', $content);
        $this->assertStringContainsString('addEventListener(', $content);
    }

    public function test_leads_view_avoids_inner_html_sinks_for_rows_and_modal(): void
    {
        $content = file_get_contents(resource_path('views/leads.blade.php'));

        $this->assertNotFalse($content);
        $this->assertStringNotContainsString('tr.innerHTML', $content);
        $this->assertStringNotContainsString("lead-modal-content').innerHTML", $content);
    }

    public function test_search_results_app_avoids_inner_html_row_sink_and_inline_delete_handler(): void
    {
        $content = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($content);
        $this->assertStringNotContainsString('tr.innerHTML', $content);
        $this->assertStringNotContainsString('onclick="deleteLead(', $content);
        $this->assertStringContainsString('toSafeHttpUrl', $content);
    }
}
