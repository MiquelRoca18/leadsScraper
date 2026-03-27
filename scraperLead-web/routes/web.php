<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\DatabasesController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\ApiProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/databases', [DatabasesController::class, 'index'])->name('databases');
Route::get('/history', [HistoryController::class, 'index'])->name('history');
Route::get('/leads', [LeadsController::class, 'index'])->name('leads');

Route::get('/api/proxy/status', [ApiProxyController::class, 'proxy'])->defaults('path', 'proxy/status');
Route::get('/api/proxy/capacity', [ApiProxyController::class, 'proxy'])->defaults('path', 'proxy/capacity');
Route::post('/api/search', [ApiProxyController::class, 'proxy'])->defaults('path', 'search');
Route::get('/api/leads', [ApiProxyController::class, 'proxy'])->defaults('path', 'leads');
Route::delete('/api/leads/{leadId}', [ApiProxyController::class, 'proxy'])->defaults('path', 'leads/{leadId}');
Route::get('/api/jobs/{jobId}', [ApiProxyController::class, 'proxy'])->defaults('path', 'jobs/{jobId}');
Route::get('/api/export/{jobId}', [ApiProxyController::class, 'proxy'])->defaults('path', 'export/{jobId}');
