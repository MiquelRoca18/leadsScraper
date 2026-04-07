<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Throwable;

class DatabasesController extends Controller
{
    public function index()
    {
        $mlUrl = rtrim((string) config('services.mapleads.api_url'), '/');
        $igUrl = rtrim((string) config('services.instaleads.api_url'), '/');

        $responses = Http::pool(fn ($pool) => [
            $pool->as('stats')->timeout(10)->acceptJson()->get("{$mlUrl}/api/stats"),
            $pool->as('proxy')->timeout(5)->acceptJson()->get("{$mlUrl}/api/proxy/status"),
            $pool->as('igStats')->timeout(5)->acceptJson()->get("{$igUrl}/api/instagram/stats"),
        ]);

        $stats = ($responses['stats']->successful()) ? ($responses['stats']->json() ?? []) : [];
        $proxyStatus = ($responses['proxy']->successful()) ? ($responses['proxy']->json() ?? []) : [];

        $instagramStats = 0;
        try {
            if ($responses['igStats']->successful()) {
                $igData = $responses['igStats']->json() ?? [];
                $instagramStats = $igData['total_leads'] ?? 0;
            }
        } catch (Throwable) {
            // InstaLeads may not be running — show 0 gracefully
        }

        return view('databases', compact('stats', 'proxyStatus', 'instagramStats'));
    }
}
