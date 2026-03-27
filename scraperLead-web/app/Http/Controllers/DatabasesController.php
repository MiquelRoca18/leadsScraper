<?php

namespace App\Http\Controllers;

use App\Services\MapLeadsApiService;

class DatabasesController extends Controller
{
    public function __construct(private MapLeadsApiService $api) {}

    public function index()
    {
        $stats = $this->api->getStats();
        $proxyStatus = $this->api->getProxyStatus();

        return view('databases', compact('stats', 'proxyStatus'));
    }
}
