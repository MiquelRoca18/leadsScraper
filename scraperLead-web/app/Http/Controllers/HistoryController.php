<?php

namespace App\Http\Controllers;

use App\Services\MapLeadsApiService;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class HistoryController extends Controller
{
    public function __construct(private MapLeadsApiService $api) {}

    public function index()
    {
        $jobs = [];
        $jobsState = 'empty';
        $jobsMessage = null;
        try {
            $jobs = $this->api->getJobs(200);
            $jobsState = empty($jobs) ? 'empty' : 'ok';
        } catch (ConnectionException) {
            $jobsState = 'timeout';
            $jobsMessage = 'No se pudo cargar el historial por tiempo de espera.';
        } catch (Throwable) {
            $jobsState = 'upstream_error';
            $jobsMessage = 'No se pudo cargar el historial por un error del backend.';
        }

        $proxyStatus = [];
        $proxyState = 'unavailable';
        try {
            $proxyStatus = $this->api->getProxyStatus();
            $proxyState = empty($proxyStatus) ? 'unavailable' : 'ok';
        } catch (Throwable) {
            $proxyState = 'unavailable';
        }

        return view('history', compact('jobs', 'jobsState', 'jobsMessage', 'proxyStatus', 'proxyState'));
    }
}
