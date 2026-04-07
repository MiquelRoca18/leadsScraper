<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\DatabasesController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\ApiProxyController;
use App\Http\Controllers\InstagramController;
use App\Http\Controllers\InstaLeadsProxyController;
use Illuminate\Support\Facades\Route;

// ── Google Maps routes ────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/databases', [DatabasesController::class, 'index'])->name('databases');
Route::get('/history', [HistoryController::class, 'index'])->name('history');
Route::get('/leads', [LeadsController::class, 'index'])->name('leads');

// Google Maps API proxy (→ localhost:8000)
Route::get('/api/proxy/status', [ApiProxyController::class, 'proxy'])->defaults('path', 'proxy/status');
Route::get('/api/proxy/capacity', [ApiProxyController::class, 'proxy'])->defaults('path', 'proxy/capacity');
Route::post('/api/search', [ApiProxyController::class, 'proxy'])->defaults('path', 'search');
Route::get('/api/leads', [ApiProxyController::class, 'proxy'])->defaults('path', 'leads');
Route::delete('/api/leads/{leadId}', [ApiProxyController::class, 'proxy'])->defaults('path', 'leads/{leadId}');
Route::get('/api/jobs/{jobId}', [ApiProxyController::class, 'proxy'])->defaults('path', 'jobs/{jobId}');
Route::get('/api/export/{jobId}', [ApiProxyController::class, 'proxy'])->defaults('path', 'export/{jobId}');

// ── Instagram routes ──────────────────────────────────────────────────────────
Route::get('/instagram', [InstagramController::class, 'index'])->name('instagram');

// Instagram API proxy (→ localhost:8002)
Route::get('/api/instagram/health', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'instagram/health');
Route::get('/api/instagram/debug/last', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'instagram/debug/last');
Route::post('/api/instagram/diagnose', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'instagram/diagnose');
Route::get('/api/instagram/profile/{username}', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'instagram/profile/{username}');
Route::post('/api/instagram/search', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'instagram/search');
Route::get('/api/instagram/jobs', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'instagram/jobs');
Route::get('/api/instagram/jobs/{jobId}', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'instagram/jobs/{jobId}');
Route::get('/api/instagram/leads', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'instagram/leads');
Route::get('/api/instagram/export/{jobId}', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'instagram/export/{jobId}');
// Fase 2 (sin cuentas): eliminar endpoints de sessions/login.
Route::get('/api/instagram/proxy/status', [InstaLeadsProxyController::class, 'proxy'])->defaults('path', 'proxy/status');
