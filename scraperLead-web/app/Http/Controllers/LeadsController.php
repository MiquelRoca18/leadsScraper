<?php

namespace App\Http\Controllers;

use App\Services\MapLeadsApiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Throwable;

class LeadsController extends Controller
{
    public function __construct(private MapLeadsApiService $api) {}

    public function index(Request $request)
    {
        $jobId = $request->query('job_id');

        $job = [];
        $jobState = $jobId ? 'upstream_error' : 'empty';
        $jobMessage = null;
        if ($jobId) {
            try {
                $job = $this->api->getJob($jobId);
                $jobState = empty($job) ? 'empty' : 'ok';
            } catch (ConnectionException) {
                $jobState = 'timeout';
                $jobMessage = 'No se pudo cargar el detalle del job por tiempo de espera.';
            } catch (Throwable) {
                $jobState = 'upstream_error';
                $jobMessage = 'No se pudo cargar el detalle del job por un error del backend.';
            }
        }

        $leads = [];
        $leadsState = 'empty';
        $leadsMessage = null;
        try {
            $leads = $this->api->getLeads($jobId ?: null);
            $leadsState = empty($leads) ? 'empty' : 'ok';
        } catch (ConnectionException) {
            $leadsState = 'timeout';
            $leadsMessage = 'No se pudieron cargar los leads por tiempo de espera.';
        } catch (Throwable) {
            $leadsState = 'upstream_error';
            $leadsMessage = 'No se pudieron cargar los leads por un error del backend.';
        }

        $proxyStatus = [];
        $proxyState = 'unavailable';
        try {
            $proxyStatus = $this->api->getProxyStatus();
            $proxyState = empty($proxyStatus) ? 'unavailable' : 'ok';
        } catch (Throwable) {
            $proxyState = 'unavailable';
        }

        return view(
            'leads',
            compact(
                'job',
                'jobState',
                'jobMessage',
                'leads',
                'leadsState',
                'leadsMessage',
                'jobId',
                'proxyStatus',
                'proxyState'
            )
        );
    }
}
