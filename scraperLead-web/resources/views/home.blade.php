@extends('layouts.app')

@section('title', 'Scraper Lead')

@section('content')

  {{-- Hero --}}
  <div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-1">¿Dónde quieres buscar leads?</h1>
    <p class="text-slate-500 text-sm">Selecciona la plataforma desde la que quieres extraer leads.</p>
  </div>

  {{-- Platform cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-10">

    {{-- Google Maps --}}
    <a href="{{ route('search') }}" class="group bg-white rounded-2xl border border-slate-200 p-5 flex flex-col gap-4 hover:border-blue-400 hover:shadow-md transition-all no-underline">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-[#e8f0fe] flex items-center justify-center flex-shrink-0">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
            <path d="M12 2C8.134 2 5 5.134 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.866-3.134-7-7-7z" fill="#EA4335"/>
            <circle cx="12" cy="9" r="2.8" fill="white"/>
          </svg>
        </div>
        <div>
          <div class="font-semibold text-slate-800 text-[15px]">Google Maps</div>
          <div class="text-slate-500 text-sm mt-0.5">Extrae emails de negocios locales a partir de una búsqueda en Google Maps.</div>
        </div>
      </div>
      <div class="flex items-center justify-end text-blue-600 text-sm font-medium group-hover:gap-2 transition-all gap-1">
        Empezar
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
        </svg>
      </div>
    </a>

    {{-- Instagram --}}
    <a href="{{ route('instagram') }}" class="group bg-white rounded-2xl border border-slate-200 p-5 flex flex-col gap-4 hover:border-purple-400 hover:shadow-md transition-all no-underline">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-100 to-purple-100 flex items-center justify-center flex-shrink-0">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke="url(#ig-grad-home)" stroke-width="2"/>
            <circle cx="12" cy="12" r="4" stroke="url(#ig-grad-home)" stroke-width="2"/>
            <circle cx="17.5" cy="6.5" r="1" fill="#C13584"/>
            <defs>
              <linearGradient id="ig-grad-home" x1="2" y1="22" x2="22" y2="2" gradientUnits="userSpaceOnUse">
                <stop stop-color="#F58529"/>
                <stop offset="0.5" stop-color="#C13584"/>
                <stop offset="1" stop-color="#833AB4"/>
              </linearGradient>
            </defs>
          </svg>
        </div>
        <div>
          <div class="font-semibold text-slate-800 text-[15px]">Instagram</div>
          <div class="text-slate-500 text-sm mt-0.5">Extrae emails de seguidores de una cuenta o de perfiles que usan un hashtag.</div>
        </div>
      </div>
      <div class="flex items-center justify-end text-purple-600 text-sm font-medium group-hover:gap-2 transition-all gap-1">
        Empezar
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
        </svg>
      </div>
    </a>

  </div>

  {{-- Recent activity --}}
  <div>
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-base font-semibold text-slate-800">Actividad reciente</h2>
      <a href="{{ route('history') }}" class="text-sm text-blue-600 hover:underline">Ver todo →</a>
    </div>

    @if(($jobsState ?? null) === 'upstream_error' || ($jobsState ?? null) === 'timeout')
      <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-sm text-amber-800">
        <p class="font-medium">{{ $jobsMessage ?? 'No se pudo cargar la actividad reciente.' }}</p>
        <p class="mt-1 text-amber-700">El servicio de datos puede estar caído temporalmente.</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
          <a href="{{ route('home') }}" class="text-amber-900 underline font-medium">Reintentar</a>
          <a href="{{ route('search') }}" class="text-blue-700 underline font-medium">Iniciar extracción manual</a>
        </div>
      </div>
    @elseif(empty($jobs))
      <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-400 text-sm">
        Aún no has hecho ninguna extracción.
        <a href="{{ route('search') }}" class="text-blue-600 hover:underline ml-1">Empieza ahora →</a>
      </div>
    @else
      <div class="flex flex-col gap-2">
        @foreach($jobs as $job)
        <a href="{{ route('leads', ['job_id' => $job['job_id']]) }}" class="bg-white rounded-xl border border-slate-200 px-4 py-3 flex items-center gap-4 hover:border-blue-300 hover:shadow-sm transition-all no-underline group">
          <div class="w-8 h-8 rounded-lg bg-[#e8f0fe] flex items-center justify-center flex-shrink-0">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M12 2C8.134 2 5 5.134 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.866-3.134-7-7-7z" fill="#EA4335"/>
              <circle cx="12" cy="9" r="2.5" fill="white"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-medium text-slate-800 text-sm truncate">{{ $job['query'] ?? '—' }}</div>
            <div class="text-slate-400 text-xs truncate">{{ $job['location'] ?? 'Sin ubicación' }}</div>
          </div>
          <div class="flex items-center gap-4 flex-shrink-0">
            <div class="text-center hidden sm:block">
              <div class="text-sm font-semibold text-slate-700">{{ $job['total'] ?? 0 }}</div>
              <div class="text-[10px] text-slate-400">Negocios</div>
            </div>
            <div class="text-center hidden sm:block">
              <div class="text-sm font-semibold text-blue-600">{{ $job['emails_found'] ?? 0 }}</div>
              <div class="text-[10px] text-slate-400">Emails</div>
            </div>
            @php
              $statusClasses = [
                'done'    => 'bg-green-100 text-green-700',
                'failed'  => 'bg-red-100 text-red-700',
                'running' => 'bg-blue-100 text-blue-700',
              ];
              $statusLabels = ['done' => 'Completado', 'failed' => 'Error', 'running' => 'En curso'];
              $st = $job['status'] ?? 'done';
            @endphp
            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusClasses[$st] ?? 'bg-slate-100 text-slate-600' }}">
              {{ $statusLabels[$st] ?? $st }}
            </span>
          </div>
        </a>
        @endforeach
      </div>
    @endif
  </div>

@endsection
