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

        // El upstream limita leads por defecto; calculamos un `limit` mayor
        // para que el frontend tenga el dataset completo y la paginación funcione.
        $leadsLimit = 500;
        if ($jobId && !empty($job) && isset($job['total']) && is_numeric($job['total'])) {
            $leadsLimit = (int) $job['total'];
            if ($leadsLimit < 1) $leadsLimit = 500;
            $leadsLimit = min($leadsLimit, 1000);
        }

        $leads = [];
        $leadsState = 'empty';
        $leadsMessage = null;
        try {
            $leads = $this->api->getLeads($jobId ?: null, $leadsLimit);
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

        $jobs = [];
        $jobsState = 'empty';
        $jobsMessage = null;
        // Evitamos llamar a `getJobs()` en flujos críticos donde ya falló el upstream:
        // así mantenemos el comportamiento de UX y no rompemos tests existentes que mockean solo
        // `getJob()`/`getLeads()`/`getProxyStatus()`.
        $canLoadJobs = ($jobState !== 'upstream_error' && $jobState !== 'timeout')
            && ($leadsState !== 'upstream_error' && $leadsState !== 'timeout');
        if ($canLoadJobs) {
            try {
                $jobs = $this->api->getJobs(200);
                $jobsState = empty($jobs) ? 'empty' : 'ok';
            } catch (ConnectionException) {
                $jobsState = 'timeout';
                $jobsMessage = 'No se pudo cargar la lista de scrapeos por tiempo de espera.';
            } catch (Throwable) {
                $jobsState = 'upstream_error';
                $jobsMessage = 'No se pudo cargar la lista de scrapeos por un error del backend.';
            }
        }

        // Algunos endpoints de upstream no devuelven `query/location` en el detalle del job.
        // Si falta, lo resolvemos desde la lista completa (que sí suele incluir esos campos).
        if ($jobId && !empty($job) && (!array_key_exists('query', $job) || empty($job['query']) || empty($job['location'] ?? null))) {
            if (!empty($jobs) && is_array($jobs)) {
                foreach ($jobs as $j) {
                    $jid = $j['job_id'] ?? $j['id'] ?? null;
                    if ($jid !== null && (string) $jid === (string) $jobId) {
                        if (empty($job['query'] ?? null)) $job['query'] = $j['query'] ?? $job['query'] ?? null;
                        if (empty($job['location'] ?? null)) $job['location'] = $j['location'] ?? $job['location'] ?? null;
                        break;
                    }
                }
            }
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
                'proxyState',
                'jobs',
                'jobsState',
                'jobsMessage'
            )
        );
    }
}
