<?php

namespace App\Http\Controllers;

use App\Services\MapLeadsApiService;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class HomeController extends Controller
{
    public function __construct(private MapLeadsApiService $api) {}

    public function index()
    {
        $jobs = [];
        $jobsState = 'empty';
        $jobsMessage = null;
        try {
            $jobs = $this->api->getJobs(3);
            $jobsState = empty($jobs) ? 'empty' : 'ok';
        } catch (ConnectionException) {
            $jobsState = 'timeout';
            $jobsMessage = 'No se pudo cargar la actividad reciente por tiempo de espera.';
        } catch (Throwable) {
            $jobsState = 'upstream_error';
            $jobsMessage = 'No se pudo cargar la actividad reciente por un error del backend.';
        }

        $proxyStatus = [];
        $proxyState = 'unavailable';
        try {
            $proxyStatus = $this->api->getProxyStatus();
            $proxyState = empty($proxyStatus) ? 'unavailable' : 'ok';
        } catch (Throwable) {
            $proxyState = 'unavailable';
        }

        return view('home', compact('jobs', 'jobsState', 'jobsMessage', 'proxyStatus', 'proxyState'));
    }
}
