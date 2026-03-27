<?php

namespace App\Http\Controllers;

use App\Services\MapLeadsApiService;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class SearchController extends Controller
{
    public function __construct(private MapLeadsApiService $api) {}

    public function index()
    {
        $proxyStatus = [];
        $proxyState = 'unavailable';
        $proxyMessage = null;
        try {
            $proxyStatus = $this->api->getProxyStatus();
            $proxyState = empty($proxyStatus) ? 'unavailable' : 'ok';
        } catch (ConnectionException) {
            $proxyState = 'timeout';
            $proxyMessage = 'El backend no responde (timeout). Puedes reintentar en unos segundos.';
        } catch (Throwable) {
            $proxyState = 'upstream_error';
            $proxyMessage = 'No se pudo obtener el estado del backend. Puedes reintentar.';
        }

        return view('search', compact('proxyStatus', 'proxyState', 'proxyMessage'));
    }
}
