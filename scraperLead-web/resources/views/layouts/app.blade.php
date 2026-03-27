<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'Scraper Lead')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  @stack('head')
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-[Inter,system-ui,sans-serif]">

<div class="flex h-screen overflow-hidden">

  {{-- ── Sidebar ── --}}
  <aside class="w-56 flex-shrink-0 bg-slate-900 flex flex-col overflow-y-auto overflow-x-hidden">

    {{-- Logo --}}
    <div class="px-4 pt-5 pb-4">
      <a href="{{ route('home') }}" class="flex items-center gap-2.5 no-underline">
        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center flex-shrink-0">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2C8.134 2 5 5.134 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.866-3.134-7-7-7z"/>
            <circle cx="12" cy="9" r="2.5"/>
          </svg>
        </div>
        <span class="text-white font-semibold text-[15px] tracking-tight">Scraper Lead</span>
      </a>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-2 pb-4 space-y-0.5">

      <p class="text-[10px] font-semibold tracking-[0.08em] text-white/30 uppercase px-2 pt-3 pb-1 select-none">Buscar</p>

      <x-sidebar-link
        :href="route('home')"
        :active="request()->routeIs('home')"
        label="Obtener Leads"
        icon='<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>'
      />

      <p class="text-[10px] font-semibold tracking-[0.08em] text-white/30 uppercase px-2 pt-4 pb-1 select-none">Datos</p>

      <x-sidebar-link
        :href="route('databases')"
        :active="request()->routeIs('databases')"
        label="Bases de datos"
        icon='<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'
      />

      <x-sidebar-link
        :href="route('history')"
        :active="request()->routeIs('history')"
        label="Historial"
        icon='<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'
      />
    </nav>

    {{-- Proxy widget --}}
    <x-proxy-status :proxy-status="($proxyStatus ?? [])" />
  </aside>

  {{-- ── Main ── --}}
  <div class="flex-1 overflow-y-auto">
    <div class="max-w-5xl mx-auto px-6 py-8">
      @yield('content')
    </div>
  </div>

</div>

@stack('scripts')
</body>
</html>
