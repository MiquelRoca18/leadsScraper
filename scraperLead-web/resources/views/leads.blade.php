@extends('layouts.app')

@section('title', $jobId ? "Leads #{$jobId} — Scraper Lead" : 'Todos los leads — Scraper Lead')

@push('head')
<style>
  /* Filter pill groups */
  .fpill {
    padding: 5px 11px; font-size: 12px; border: 1px solid #e2e8f0;
    color: #64748b; cursor: pointer; transition: all .15s; background: white;
    white-space: nowrap; line-height: 1.4;
  }
  .fpill:first-child { border-radius: 8px 0 0 8px; }
  .fpill:last-child  { border-radius: 0 8px 8px 0; }
  .fpill + .fpill    { border-left: none; }
  .fpill.on          { background: #2563eb; color: white; border-color: #2563eb; font-weight: 500; }
  .fpill:not(.on):hover { background: #f8fafc; }
  /* Export toggle */
  .etab { padding: 6px 14px; font-size: 13px; border: 1px solid #e2e8f0; color: #64748b; cursor: pointer; transition: all .15s; background: white; }
  .etab:first-child { border-radius: 8px 0 0 8px; }
  .etab:last-child  { border-radius: 0 8px 8px 0; border-left: none; }
  .etab.on { background: #2563eb; color: white; border-color: #2563eb; font-weight: 500; }
  .etab:not(.on):hover { background: #f8fafc; }
</style>
@endpush

@section('content')

  @if(($jobState ?? null) === 'upstream_error' || ($jobState ?? null) === 'timeout' || ($leadsState ?? null) === 'upstream_error' || ($leadsState ?? null) === 'timeout')
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 mb-4 text-sm" role="status" aria-live="polite">
      <p class="font-medium">
        {{ $leadsMessage ?? $jobMessage ?? 'No se pudo cargar la información solicitada desde el backend.' }}
      </p>
      <p class="mt-1 text-amber-700">No se mostrará un vacío silencioso cuando el problema sea de upstream.</p>
      <div class="mt-2 flex items-center gap-4">
        <a href="{{ route('leads', $jobId ? ['job_id' => $jobId] : []) }}" class="underline font-medium text-amber-900">Reintentar</a>
        <a href="{{ route('history') }}" class="underline font-medium text-blue-700">Volver al historial</a>
      </div>
    </div>
  @endif

  @if($jobId && !empty($job))
  {{-- Job detail header --}}
  @php
    $stClasses = ['done' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700', 'running' => 'bg-blue-100 text-blue-700'];
    $stLabels  = ['done' => 'Completado', 'failed' => 'Error', 'running' => 'En curso'];
    $st = $job['status'] ?? 'done';
    $jobDate = !empty($job['started_at']) ? \Carbon\Carbon::parse($job['started_at'])->locale('es')->isoFormat('D MMM YYYY') : '—';
  @endphp
  <div class="flex items-start gap-4 mb-6 flex-wrap">
    <a href="{{ route('history') }}" class="flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition mt-1 no-underline flex-shrink-0">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
      </svg>
      Historial
    </a>
    <div class="flex-1 min-w-0">
      <h1 class="text-xl font-bold text-slate-800 truncate">{{ $job['query'] ?? "Job #{$jobId}" }}</h1>
      <p class="text-sm text-slate-400 mt-0.5">{{ $job['location'] ?? 'Sin ubicación' }} · {{ $jobDate }} · <span class="text-xs px-1.5 py-0.5 rounded-full font-medium inline-block {{ $stClasses[$st] ?? 'bg-slate-100 text-slate-600' }}">{{ $stLabels[$st] ?? $st }}</span></p>
    </div>
    <div class="flex items-center gap-6 flex-shrink-0">
      <div class="text-center">
        <div class="text-lg font-bold text-slate-800">{{ $job['total'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-400">Negocios</div>
      </div>
      <div class="text-center">
        <div class="text-lg font-bold text-blue-600">{{ $job['emails_found'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-400">Emails</div>
      </div>
      <button type="button" data-action="open-export"
              class="px-4 py-2 border border-slate-200 text-slate-600 text-sm rounded-lg hover:bg-slate-50 transition">
        Exportar CSV
      </button>
    </div>
  </div>
  @else
  <div class="mb-5">
    <h1 class="text-2xl font-bold text-slate-800 mb-1">Todos los leads</h1>
    <p class="text-slate-500 text-sm">Historial completo de leads extraídos.</p>
  </div>
  @endif

  {{-- Filters --}}
  <div class="bg-white border border-slate-200 rounded-xl px-4 py-3 mb-4 space-y-3">
    {{-- Row 1: text inputs + actions --}}
    <div class="flex flex-wrap items-center gap-2">
      <input id="filter-name" type="text" placeholder="Buscar por nombre..."
             class="flex-1 min-w-[160px] px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
      <input id="filter-location" type="text" placeholder="Localidad..."
             class="w-[140px] px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
      <button id="reload-leads-btn" type="button" class="px-3 py-2 border border-slate-200 text-slate-600 text-sm rounded-lg hover:bg-slate-50 transition">
        Recargar
      </button>
      <span id="results-count" class="text-sm text-slate-400"></span>
      <button type="button" data-action="open-export"
              class="px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition ml-auto">
        Exportar CSV
      </button>
    </div>
    {{-- Row 2: pill filters --}}
    <div class="flex flex-wrap items-center gap-3">
      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-400 font-medium w-16 text-right flex-shrink-0">Email:</span>
        <div class="flex">
          <button type="button" class="fpill on" data-group="email" data-val="">Todos</button>
          <button type="button" class="fpill" data-group="email" data-val="yes">Verificado</button>
          <button type="button" class="fpill" data-group="email" data-val="no">Sin email</button>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-400 font-medium w-16 text-right flex-shrink-0">Teléfono:</span>
        <div class="flex">
          <button type="button" class="fpill on" data-group="phone" data-val="">Todos</button>
          <button type="button" class="fpill" data-group="phone" data-val="yes">Con tel.</button>
          <button type="button" class="fpill" data-group="phone" data-val="no">Sin tel.</button>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-400 font-medium w-16 text-right flex-shrink-0">Web:</span>
        <div class="flex">
          <button type="button" class="fpill on" data-group="web" data-val="">Todos</button>
          <button type="button" class="fpill" data-group="web" data-val="yes">Web real</button>
          <button type="button" class="fpill" data-group="web" data-val="no">Sin web</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-100">
          <tr>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Nombre</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Dirección</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Teléfono</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Web</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Email</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Estado</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Categoría</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Rating</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody id="leads-tbody"></tbody>
      </table>
    </div>

    <div id="pagination" class="hidden flex items-center justify-center gap-2 px-5 py-4 border-t border-slate-100">
      <button id="page-first" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">«</button>
      <button id="page-prev"  class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">‹</button>
      <span id="page-info" class="text-xs text-slate-500 mx-2"></span>
      <button id="page-next"  class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">›</button>
      <button id="page-last"  class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">»</button>
    </div>

    <div id="empty-state" class="py-16 text-center text-slate-400">
      <svg class="mx-auto mb-3 opacity-30" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <p class="text-sm">
        @if(($leadsState ?? null) === 'empty')
          No hay leads para mostrar.
        @else
          No hay leads disponibles temporalmente.
        @endif
      </p>
    </div>
  </div>

  {{-- Export modal --}}
  <div id="export-modal"
       class="fixed inset-0 z-50 flex items-center justify-center"
       style="display:none; background:rgba(15,23,42,.65); backdrop-filter:blur(2px); padding: 5rem 2rem">
    <div class="bg-white rounded-2xl shadow-2xl w-full overflow-hidden" style="max-width: 360px; max-height: calc(100vh - 10rem); overflow-y: auto">
      <div class="px-6 pt-6 pb-5 border-b border-slate-100">
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-base font-semibold text-slate-800">Exportar CSV</h2>
            <p id="export-modal-info" class="text-xs text-slate-400 mt-1"></p>
          </div>
          <button id="export-close-x-btn" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition text-sm ml-4 flex-shrink-0">✕</button>
        </div>
      </div>
      <div class="px-6 py-6">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">¿Cuántos leads exportar?</p>
        <div class="flex mb-5">
          <button id="et-all" type="button" class="etab on">Todos</button>
          <button id="et-num" type="button" class="etab">Número específico</button>
        </div>
        <div id="export-num-section" style="display:none">
          <div class="relative">
            <input id="export-limit" type="number" min="1" placeholder="Ej: 50"
                   class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
            <span id="export-limit-max" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></span>
          </div>
          <p id="export-limit-error" class="text-xs text-red-500 mt-1.5 hidden"></p>
        </div>
      </div>
      <div class="px-6 pb-6 flex gap-3 border-t border-slate-100 pt-5">
        <button id="export-cancel-btn" type="button" class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-600 text-sm rounded-xl hover:bg-slate-50 transition font-medium">Cancelar</button>
        <button id="export-confirm-btn" type="button" class="flex-1 px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition">Descargar</button>
      </div>
    </div>
  </div>

  {{-- Lead detail modal --}}
  <div id="lead-modal"
       class="fixed inset-0 z-50 flex items-center justify-center"
       style="display:none; background:rgba(15,23,42,.65); backdrop-filter:blur(2px); padding: 5rem 2rem">
    <div class="bg-white rounded-2xl shadow-2xl w-full overflow-hidden" style="max-width: 420px; max-height: calc(100vh - 10rem); overflow-y: auto">
      <div id="lead-modal-content"></div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
const PAGE_SIZE = 25;
const JOB_ID = @json($jobId);

const SOCIAL_DOMAINS = [
  'instagram.com','tiktok.com','facebook.com','twitter.com','x.com',
  'youtube.com','linkedin.com','pinterest.com','snapchat.com','threads.net'
];

let allLeads = @json($leads ?? []);
let filteredLeads = [];
let currentPage = 1;
const _leadMap = {};

// ── Filter state ───────────────────────────────────────────────
const fstate = { email: '', phone: '', web: '' };

function setFpill(btn) {
  const group = btn.dataset.group;
  fstate[group] = btn.dataset.val;
  document.querySelectorAll(`.fpill[data-group="${group}"]`).forEach(b => b.classList.remove('on'));
  btn.classList.add('on');
  applyFilters();
}

// ── Utils ──────────────────────────────────────────────────────
function escHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function safeText(value, fallback = '—') {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text ? text : fallback;
}

function toSafeHttpUrl(value) {
  if (!value) return null;
  try {
    const parsed = new URL(String(value), window.location.origin);
    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') return null;
    return parsed.href;
  } catch (_) {
    return null;
  }
}

function emailStatusClass(status) {
  if (status === 'valid')   return 'text-green-600 font-medium';
  if (status === 'invalid') return 'text-red-500';
  return 'text-slate-400';
}

function isSocialUrl(url) {
  if (!url) return false;
  const lower = url.toLowerCase();
  return SOCIAL_DOMAINS.some(d => lower.includes(d));
}

// ── Table rendering ────────────────────────────────────────────
function renderPage(page) {
  const total = filteredLeads.length;
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  currentPage = Math.min(Math.max(1, page), totalPages);
  const tbody = document.getElementById('leads-tbody');
  const empty = document.getElementById('empty-state');
  if (!total) {
    if (tbody) tbody.replaceChildren();
    if (empty) empty.classList.remove('hidden');
    updatePagination(0);
    return;
  }
  if (empty) empty.classList.add('hidden');
  const start = (currentPage - 1) * PAGE_SIZE;
  const slice = filteredLeads.slice(start, start + PAGE_SIZE);
  tbody.replaceChildren();
  for (const lead of slice) {
    _leadMap[lead.id] = lead;
    const tr = document.createElement('tr');
    tr.className = 'border-b border-slate-100 last:border-0 hover:bg-slate-50 transition cursor-pointer';
    tr.addEventListener('click', () => openLeadModal(lead.id));

    const makeCell = (className, text, title = null) => {
      const td = document.createElement('td');
      td.className = className;
      td.textContent = text;
      if (title) td.title = title;
      return td;
    };

    const businessName = safeText(lead.business_name);
    const address = safeText(lead.address);
    const phone = safeText(lead.phone);
    const email = safeText(lead.email);
    const emailStatus = safeText(lead.email_status);
    const category = safeText(lead.category);
    const rating = safeText(lead.rating);

    tr.appendChild(makeCell('px-4 py-3 font-medium text-slate-800 max-w-[160px] truncate', businessName, businessName));
    tr.appendChild(makeCell('px-4 py-3 text-slate-500 max-w-[160px] truncate', address, address));
    tr.appendChild(makeCell('px-4 py-3 text-slate-600', phone));

    const websiteTd = document.createElement('td');
    websiteTd.className = 'px-4 py-3 max-w-[140px] truncate';
    const safeWebsite = toSafeHttpUrl(lead.website);
    if (safeWebsite) {
      const websiteLink = document.createElement('a');
      websiteLink.href = safeWebsite;
      websiteLink.target = '_blank';
      websiteLink.rel = 'noopener noreferrer';
      websiteLink.className = 'text-blue-600 hover:underline text-xs';
      websiteLink.textContent = safeText(lead.website);
      websiteLink.addEventListener('click', e => e.stopPropagation());
      websiteTd.appendChild(websiteLink);
    } else {
      websiteTd.textContent = '—';
    }
    tr.appendChild(websiteTd);

    tr.appendChild(makeCell(`px-4 py-3 ${emailStatusClass(lead.email_status)}`, email));
    tr.appendChild(makeCell('px-4 py-3 text-slate-500 text-xs', emailStatus));
    tr.appendChild(makeCell('px-4 py-3 text-slate-500 text-xs max-w-[120px] truncate', category, category));
    tr.appendChild(makeCell('px-4 py-3 text-slate-600', rating));

    const actionsTd = document.createElement('td');
    actionsTd.className = 'px-4 py-3';
    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.className = 'w-7 h-7 rounded-lg border border-red-200 text-red-400 hover:bg-red-50 hover:text-red-600 hover:border-red-300 transition text-xs flex items-center justify-center';
    deleteBtn.textContent = '✕';
    deleteBtn.addEventListener('click', e => {
      e.stopPropagation();
      deleteLead(lead.id);
    });
    actionsTd.appendChild(deleteBtn);
    tr.appendChild(actionsTd);

    tbody.appendChild(tr);
  }
  updatePagination(total);
}

function updatePagination(total) {
  const pag  = document.getElementById('pagination');
  const info = document.getElementById('page-info');
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (!pag) return;
  pag.style.display = total > PAGE_SIZE ? 'flex' : 'none';
  if (info) {
    const s = (currentPage - 1) * PAGE_SIZE + 1;
    const e = Math.min(currentPage * PAGE_SIZE, total);
    info.textContent = `${s}–${e} de ${total}`;
  }
  document.getElementById('page-first').disabled = currentPage <= 1;
  document.getElementById('page-prev').disabled  = currentPage <= 1;
  document.getElementById('page-next').disabled  = currentPage >= totalPages;
  document.getElementById('page-last').disabled  = currentPage >= totalPages;
}

function applyFilters(keepPage = false) {
  const nameFilter     = (document.getElementById('filter-name').value || '').toLowerCase();
  const locationFilter = (document.getElementById('filter-location').value || '').toLowerCase();

  filteredLeads = allLeads;
  if (nameFilter)     filteredLeads = filteredLeads.filter(l => (l.business_name || '').toLowerCase().includes(nameFilter));
  if (locationFilter) filteredLeads = filteredLeads.filter(l => (l.address || '').toLowerCase().includes(locationFilter));

  if (fstate.email === 'yes') filteredLeads = filteredLeads.filter(l => l.email_status === 'valid');
  if (fstate.email === 'no')  filteredLeads = filteredLeads.filter(l => !l.email);

  if (fstate.phone === 'yes') filteredLeads = filteredLeads.filter(l => l.phone);
  if (fstate.phone === 'no')  filteredLeads = filteredLeads.filter(l => !l.phone);

  if (fstate.web === 'yes') filteredLeads = filteredLeads.filter(l => l.website && !isSocialUrl(l.website));
  if (fstate.web === 'no')  filteredLeads = filteredLeads.filter(l => !l.website || isSocialUrl(l.website));

  const page = keepPage ? currentPage : 1;
  renderPage(page);

  const count = document.getElementById('results-count');
  if (count) count.textContent = filteredLeads.length ? `${filteredLeads.length} leads` : '';
}

async function reloadLeads() {
  const url = JOB_ID ? `/api/leads?job_id=${JOB_ID}` : '/api/leads';
  try {
    const res = await fetch(url);
    if (!res.ok) return;
    allLeads = await res.json();
    applyFilters();
  } catch (_) {}
}

async function deleteLead(id) {
  const res = await fetch(`/api/leads/${id}`, { method: 'DELETE' });
  if (res.ok) {
    allLeads = allLeads.filter(l => l.id !== id);
    delete _leadMap[id];
    applyFilters(true);
  }
}

// ── CSV export ─────────────────────────────────────────────────
let _exportMode = 'all'; // 'all' | 'num'

function setExportTab(mode) {
  _exportMode = mode;
  document.getElementById('et-all').classList.toggle('on', mode === 'all');
  document.getElementById('et-num').classList.toggle('on', mode === 'num');
  document.getElementById('export-num-section').style.display = mode === 'num' ? 'block' : 'none';
  if (mode === 'num') {
    const inp = document.getElementById('export-limit');
    inp.max = filteredLeads.length;
    document.getElementById('export-limit-max').textContent = `máx ${filteredLeads.length}`;
    validateExportLimit();
  }
}

function validateExportLimit() {
  const inp = document.getElementById('export-limit');
  const err = document.getElementById('export-limit-error');
  const btn = document.getElementById('export-confirm-btn');
  const val = parseInt(inp.value);
  const max = filteredLeads.length;
  if (isNaN(val) || val <= 0) {
    err.textContent = 'Introduce un número mayor que 0.';
    err.classList.remove('hidden');
    btn.disabled = true;
    btn.classList.add('opacity-50', 'cursor-not-allowed');
    return false;
  }
  if (val > max) {
    err.textContent = `Solo hay ${max} leads disponibles con los filtros actuales.`;
    err.classList.remove('hidden');
    inp.value = max;
    btn.disabled = false;
    btn.classList.remove('opacity-50', 'cursor-not-allowed');
    return false;
  }
  err.classList.add('hidden');
  btn.disabled = false;
  btn.classList.remove('opacity-50', 'cursor-not-allowed');
  return true;
}

function buildCsvBlob(leads) {
  const cols = ['Nombre','Dirección','Teléfono','Web','Email','Estado email','Categoría','Rating','URL Maps','Fecha'];
  const keys = ['business_name','address','phone','website','email','email_status','category','rating','maps_url','scraped_at'];
  const esc  = v => `"${(v ?? '').toString().replace(/"/g, '""')}"`;
  const rows = leads.map(l => keys.map(k => esc(l[k])).join(','));
  return new Blob(['\uFEFF' + [cols.join(','), ...rows].join('\r\n')], { type: 'text/csv;charset=utf-8' });
}

function doExport() {
  _exportMode = 'all';
  document.getElementById('et-all').classList.add('on');
  document.getElementById('et-num').classList.remove('on');
  document.getElementById('export-num-section').style.display = 'none';
  document.getElementById('export-limit').value = '';
  document.getElementById('export-limit-error').classList.add('hidden');
  const btn = document.getElementById('export-confirm-btn');
  btn.disabled = false; btn.classList.remove('opacity-50','cursor-not-allowed');
  const info = document.getElementById('export-modal-info');
  if (info) info.textContent = `${filteredLeads.length} leads con los filtros actuales`;
  document.getElementById('export-modal').style.display = 'flex';
}

function closeExportModal() {
  document.getElementById('export-modal').style.display = 'none';
}

function confirmExport() {
  let leads;
  if (_exportMode === 'num') {
    if (!validateExportLimit()) return;
    const lim = parseInt(document.getElementById('export-limit').value);
    leads = filteredLeads.slice(0, lim);
  } else {
    leads = filteredLeads;
  }
  if (!leads.length) { closeExportModal(); return; }
  const blob = buildCsvBlob(leads);
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = `leads_${JOB_ID || 'todos'}_${Date.now()}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  closeExportModal();
}

// ── Lead detail modal ──────────────────────────────────────────
function openLeadModal(id) {
  const lead = _leadMap[id];
  if (!lead) return;
  const content = document.getElementById('lead-modal-content');
  if (!content) return;
  content.replaceChildren();

  const createLabel = text => {
    const span = document.createElement('span');
    span.className = 'text-[10px] font-semibold text-slate-400 uppercase tracking-widest';
    span.textContent = text;
    return span;
  };

  const createField = (label, valueNode) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'flex flex-col gap-1.5';
    wrapper.appendChild(createLabel(label));
    const value = document.createElement('div');
    value.className = 'text-sm text-slate-700 leading-relaxed';
    value.appendChild(valueNode);
    wrapper.appendChild(value);
    return wrapper;
  };

  const header = document.createElement('div');
  header.className = 'px-6 pt-6 pb-5 border-b border-slate-100 flex items-start gap-3';

  const headerInfo = document.createElement('div');
  headerInfo.className = 'flex-1 min-w-0';
  const title = document.createElement('h2');
  title.className = 'text-lg font-bold text-slate-800 truncate';
  title.textContent = safeText(lead.business_name);
  headerInfo.appendChild(title);
  if (lead.category) {
    const category = document.createElement('p');
    category.className = 'text-xs text-slate-500 mt-1 font-medium';
    category.textContent = safeText(lead.category);
    headerInfo.appendChild(category);
  }

  const headerActions = document.createElement('div');
  headerActions.className = 'flex items-center gap-2 flex-shrink-0';
  if (lead.rating !== null && lead.rating !== undefined && String(lead.rating).trim() !== '') {
    const ratingWrap = document.createElement('div');
    ratingWrap.className = 'flex items-center gap-1.5 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5';
    const star = document.createElement('span');
    star.className = 'text-amber-400 text-sm';
    star.textContent = '★';
    const rating = document.createElement('span');
    rating.className = 'text-sm font-bold text-amber-700';
    rating.textContent = safeText(lead.rating);
    ratingWrap.appendChild(star);
    ratingWrap.appendChild(rating);
    headerActions.appendChild(ratingWrap);
  }
  const closeIconBtn = document.createElement('button');
  closeIconBtn.type = 'button';
  closeIconBtn.className = 'w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition text-sm';
  closeIconBtn.textContent = '✕';
  closeIconBtn.addEventListener('click', closeLeadModal);
  headerActions.appendChild(closeIconBtn);
  header.appendChild(headerInfo);
  header.appendChild(headerActions);
  content.appendChild(header);

  const body = document.createElement('div');
  body.className = 'px-6 py-5 flex flex-col gap-5';

  const row1 = document.createElement('div');
  row1.className = 'grid grid-cols-2 gap-x-6 gap-y-5';
  const addressNode = document.createElement('span');
  addressNode.className = 'break-words';
  addressNode.textContent = safeText(lead.address);
  row1.appendChild(createField('Dirección', addressNode));
  const phoneNode = document.createElement('span');
  phoneNode.textContent = safeText(lead.phone);
  row1.appendChild(createField('Teléfono', phoneNode));
  body.appendChild(row1);

  const websiteWrap = document.createElement('div');
  websiteWrap.className = 'flex flex-col gap-1.5';
  websiteWrap.appendChild(createLabel('Sitio web'));
  const websiteValue = document.createElement('div');
  const safeWebsite = toSafeHttpUrl(lead.website);
  if (safeWebsite) {
    const websiteLink = document.createElement('a');
    websiteLink.href = safeWebsite;
    websiteLink.target = '_blank';
    websiteLink.rel = 'noopener noreferrer';
    websiteLink.className = 'text-blue-600 hover:underline text-sm break-all';
    websiteLink.textContent = safeText(lead.website);
    websiteValue.appendChild(websiteLink);
    if (isSocialUrl(lead.website)) {
      const socialBadge = document.createElement('span');
      socialBadge.className = 'ml-1.5 text-[10px] bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full font-medium';
      socialBadge.textContent = 'Red social';
      websiteValue.appendChild(socialBadge);
    }
  } else {
    const emptyWebsite = document.createElement('span');
    emptyWebsite.className = 'text-slate-400 text-sm';
    emptyWebsite.textContent = '—';
    websiteValue.appendChild(emptyWebsite);
  }
  websiteWrap.appendChild(websiteValue);
  body.appendChild(websiteWrap);

  const row2 = document.createElement('div');
  row2.className = 'grid grid-cols-2 gap-x-6 gap-y-5';
  const emailWrap = document.createElement('div');
  emailWrap.className = 'flex flex-col gap-1.5';
  emailWrap.appendChild(createLabel('Email'));
  const emailValue = document.createElement('div');
  emailValue.className = `text-sm ${emailStatusClass(lead.email_status)}`;
  emailValue.textContent = safeText(lead.email);
  emailWrap.appendChild(emailValue);
  row2.appendChild(emailWrap);

  const statusWrap = document.createElement('div');
  statusWrap.className = 'flex flex-col gap-1.5';
  statusWrap.appendChild(createLabel('Estado'));
  const badge = document.createElement('span');
  const st = lead.email_status || 'pending';
  const badgeMap = {
    valid: { className: 'inline-flex items-center gap-1 text-xs font-medium bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full', text: '✓ Verificado' },
    invalid: { className: 'inline-flex items-center gap-1 text-xs font-medium bg-red-50 text-red-600 border border-red-200 px-2 py-0.5 rounded-full', text: '✕ Inválido' },
    pending: { className: 'inline-flex items-center gap-1 text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded-full', text: '· Pendiente' },
  };
  const badgeCfg = badgeMap[st] || badgeMap.pending;
  badge.className = badgeCfg.className;
  badge.textContent = badgeCfg.text;
  statusWrap.appendChild(badge);
  row2.appendChild(statusWrap);
  body.appendChild(row2);

  const mapsWrap = document.createElement('div');
  mapsWrap.className = 'flex flex-col gap-1.5';
  mapsWrap.appendChild(createLabel('URL Google Maps'));
  const mapsValue = document.createElement('div');
  const safeMapsUrl = toSafeHttpUrl(lead.maps_url);
  if (safeMapsUrl) {
    const mapsLink = document.createElement('a');
    mapsLink.href = safeMapsUrl;
    mapsLink.target = '_blank';
    mapsLink.rel = 'noopener noreferrer';
    mapsLink.className = 'text-blue-600 hover:underline text-xs break-all';
    mapsLink.textContent = safeText(lead.maps_url);
    mapsValue.appendChild(mapsLink);
  } else {
    const emptyMaps = document.createElement('span');
    emptyMaps.className = 'text-slate-400 text-sm';
    emptyMaps.textContent = '—';
    mapsValue.appendChild(emptyMaps);
  }
  mapsWrap.appendChild(mapsValue);
  body.appendChild(mapsWrap);

  const scrapedAtText = lead.scraped_at
    ? new Date(lead.scraped_at).toLocaleString('es-ES', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
    : '—';
  const scrapedNode = document.createElement('span');
  scrapedNode.textContent = scrapedAtText;
  body.appendChild(createField('Extraído el', scrapedNode));
  content.appendChild(body);

  const footer = document.createElement('div');
  footer.className = 'px-6 pb-6 flex gap-3 border-t border-slate-100 pt-5';
  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.className = 'px-4 py-2.5 border border-red-200 text-red-500 text-sm rounded-xl hover:bg-red-50 transition font-medium';
  deleteBtn.textContent = 'Eliminar';
  deleteBtn.addEventListener('click', async () => {
    await deleteLead(lead.id);
    closeLeadModal();
  });
  const closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'ml-auto px-6 py-2.5 bg-slate-800 text-white text-sm font-semibold rounded-xl hover:bg-slate-700 transition';
  closeBtn.textContent = 'Cerrar';
  closeBtn.addEventListener('click', closeLeadModal);
  footer.appendChild(deleteBtn);
  footer.appendChild(closeBtn);
  content.appendChild(footer);

  document.getElementById('lead-modal').style.display = 'flex';
}

function closeLeadModal() {
  document.getElementById('lead-modal').style.display = 'none';
}

// ── Pagination ─────────────────────────────────────────────────
document.getElementById('page-first')?.addEventListener('click', () => renderPage(1));
document.getElementById('page-prev')?.addEventListener('click',  () => renderPage(currentPage - 1));
document.getElementById('page-next')?.addEventListener('click',  () => renderPage(currentPage + 1));
document.getElementById('page-last')?.addEventListener('click',  () => renderPage(Math.ceil(filteredLeads.length / PAGE_SIZE)));

document.getElementById('filter-name')?.addEventListener('input', () => applyFilters());
document.getElementById('filter-location')?.addEventListener('input', () => applyFilters());
document.getElementById('reload-leads-btn')?.addEventListener('click', reloadLeads);

document.querySelectorAll('.fpill').forEach(btn => {
  btn.addEventListener('click', () => setFpill(btn));
});

document.querySelectorAll('[data-action="open-export"]').forEach(btn => {
  btn.addEventListener('click', doExport);
});
document.getElementById('et-all')?.addEventListener('click', () => setExportTab('all'));
document.getElementById('et-num')?.addEventListener('click', () => setExportTab('num'));
document.getElementById('export-limit')?.addEventListener('input', validateExportLimit);
document.getElementById('export-confirm-btn')?.addEventListener('click', confirmExport);
document.getElementById('export-close-x-btn')?.addEventListener('click', closeExportModal);
document.getElementById('export-cancel-btn')?.addEventListener('click', closeExportModal);
document.getElementById('lead-modal')?.addEventListener('click', e => {
  if (e.target && e.target.id === 'lead-modal') closeLeadModal();
});

// ── Init ───────────────────────────────────────────────────────
applyFilters();
</script>
@endpush
