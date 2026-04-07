<?php

namespace App\Http\Controllers;

use App\Services\InstaLeadsApiService;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class InstagramController extends Controller
{
    public function __construct(private InstaLeadsApiService $api) {}

    public function index()
    {
        $health = ['status' => 'unknown'];
        $recentJobs = [];
        $state = 'ok';
        $message = null;

        try {
            $health = $this->api->getHealth();
        } catch (ConnectionException) {
            $state = 'timeout';
            $message = 'El backend de Instagram no responde. Asegúrate de que InstaLeads está corriendo en el puerto 8002.';
        } catch (Throwable) {
            $state = 'upstream_error';
            $message = 'No se pudo cargar el estado de Instagram.';
        }

        if ($state === 'ok') {
            try {
                $recentJobs = $this->api->getJobs(3);
            } catch (Throwable) {
                $recentJobs = [];
            }
        }

        return view('instagram', compact('health', 'recentJobs', 'state', 'message'));
    }
}
