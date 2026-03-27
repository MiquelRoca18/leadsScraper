@props([
    'proxyStatus' => [],
    'hiddenWhenEmpty' => true,
])

@php
    $available = $proxyStatus['available_now'] ?? 0;
    $total = $proxyStatus['total_proxies'] ?? 0;
    $used = $proxyStatus['daily_requests_used'] ?? 0;
    $limit = $proxyStatus['daily_requests_limit'] ?? 1;
    $dailyPct = $limit > 0 ? round(($used / $limit) * 100) : 0;
    $isWarning = $available <= 5 || $dailyPct >= 50;
    $isCritical = $available <= 2 || $dailyPct >= 80;
    $colorClass = $isCritical ? 'bg-red-500' : ($isWarning ? 'bg-yellow-400' : 'bg-green-400');
    $containerClass = 'mx-3 mb-4 p-3 rounded-xl bg-white/5 border border-white/10';
    $isEmpty = $total <= 0;
    $barStyle = 'width: ' . ($isEmpty ? 0 : $dailyPct) . '%';
@endphp

<div id="sidebar-proxy" class="{{ $containerClass }} {{ $isEmpty && $hiddenWhenEmpty ? 'hidden' : '' }}">
  <div class="flex items-center gap-2 mb-2">
    <span class="w-2 h-2 rounded-full {{ $isEmpty ? 'bg-green-400' : $colorClass }} shrink-0" id="sp-dot"></span>
    <span class="text-[11px] font-medium text-slate-300" id="sp-label">
      @if($isEmpty)
        — disponibles
      @else
        {{ $available }}/{{ $total }} disponibles
      @endif
    </span>
  </div>
  <div class="w-full bg-white/10 rounded-full h-1.5">
    <div id="sp-bar"
         class="h-1.5 rounded-full {{ $isEmpty ? 'bg-green-400' : $colorClass }} transition-all"
         style="{{ $barStyle }}"></div>
  </div>
  @unless($isEmpty)
    <p class="text-[10px] text-slate-500 mt-1.5">{{ number_format($used) }} / {{ number_format($limit) }} req. hoy</p>
  @endunless
</div>
