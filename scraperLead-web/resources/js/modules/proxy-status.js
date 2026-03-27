import {
  startProxyStatusPolling,
  subscribeProxyStatus,
} from '../lib/proxy-state';

function getStatusColorClass(availableNow, dailyPct) {
  if (availableNow <= 2 || dailyPct >= 80) return 'bg-red-500';
  if (availableNow <= 5 || dailyPct >= 50) return 'bg-yellow-400';
  return 'bg-green-400';
}

function renderSearchProxyWidget(proxyStatus, isScrapingInProgress) {
  const widget = document.getElementById('proxy-widget');
  if (!widget) return;

  if (!proxyStatus || proxyStatus.total_proxies === 0) {
    widget.classList.add('hidden');
    return;
  }

  widget.classList.remove('hidden');

  const dot = document.getElementById('proxy-dot');
  const label = document.getElementById('proxy-label');
  const reqLabel = document.getElementById('proxy-requests-label');
  const barFill = document.getElementById('proxy-bar-fill');
  const pctLabel = document.getElementById('proxy-pct-label');

  const available = proxyStatus.available_now;
  const total = proxyStatus.total_proxies;
  const dailyPct = proxyStatus.daily_requests_limit > 0
    ? Math.round((proxyStatus.daily_requests_used / proxyStatus.daily_requests_limit) * 100)
    : 0;
  const colorClass = getStatusColorClass(available, dailyPct);

  if (dot) {
    dot.className = `w-2.5 h-2.5 rounded-full flex-shrink-0 ${colorClass}`;
  }

  if (barFill) {
    barFill.className = `h-1.5 rounded-full transition-all ${colorClass}`;
    barFill.style.width = `${dailyPct}%`;
  }

  if (label) {
    label.textContent = `Proxies: ${available}/${total} disponibles`;
  }

  if (reqLabel) {
    reqLabel.textContent = `Requests hoy: ${proxyStatus.daily_requests_used.toLocaleString()} / ${proxyStatus.daily_requests_limit.toLocaleString()}`;
  }

  if (pctLabel) {
    pctLabel.textContent = `${dailyPct}%`;
  }

  const dailyExhausted = proxyStatus.daily_requests_remaining === 0 && proxyStatus.total_proxies > 0;
  const startBtn = document.getElementById('start-btn');
  if (!startBtn || isScrapingInProgress()) return;

  startBtn.disabled = dailyExhausted;
  startBtn.title = dailyExhausted ? 'Límite diario de requests agotado. Se reiniciará mañana.' : '';
}

function renderSidebarProxyWidget(proxyStatus) {
  const widget = document.getElementById('sidebar-proxy');
  if (!widget) return;

  if (!proxyStatus || proxyStatus.total_proxies === 0) {
    widget.classList.add('hidden');
    return;
  }

  widget.classList.remove('hidden');

  const dot = document.getElementById('sp-dot');
  const label = document.getElementById('sp-label');
  const bar = document.getElementById('sp-bar');
  const dailyPct = proxyStatus.daily_requests_limit > 0
    ? Math.round((proxyStatus.daily_requests_used / proxyStatus.daily_requests_limit) * 100)
    : 0;
  const colorClass = getStatusColorClass(proxyStatus.available_now, dailyPct);

  if (dot) {
    dot.className = `w-2 h-2 rounded-full flex-shrink-0 ${colorClass}`;
  }

  if (label) {
    label.textContent = `${proxyStatus.available_now}/${proxyStatus.total_proxies} disponibles`;
  }

  if (bar) {
    bar.className = `h-1.5 rounded-full transition-all ${colorClass}`;
    bar.style.width = `${dailyPct}%`;
  }
}

export function initProxyStatus({ isScrapingInProgress = () => false } = {}) {
  subscribeProxyStatus((proxyStatus) => {
    renderSearchProxyWidget(proxyStatus, isScrapingInProgress);
    renderSidebarProxyWidget(proxyStatus);
  });

  startProxyStatusPolling({ intervalMs: 30000, immediate: true });
}
