<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Kinerja Penjualan</title>

    <script>
        (() => {
            let theme;
            try {
                theme = localStorage.getItem('dashboard-theme');
            } catch (error) {
                console.error('Preferensi tema tidak dapat dibaca.', error);
            }
            const dark = theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: ui-sans-serif, system-ui, "Segoe UI", Roboto, sans-serif; }
        .chart-box { position: relative; height: 260px; }
        #map { height: 290px; border-radius: .5rem; z-index: 0; }
        .state-marker { cursor: pointer; }
        .loading { opacity: .55; pointer-events: none; transition: opacity .15s; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800">
<div class="max-w-7xl mx-auto p-4 md:p-6 space-y-4" id="app">

    {{-- ===================== HEADER & FILTER ===================== --}}
    <header class="sticky top-0 z-50 bg-white rounded-xl border border-slate-200 px-4 py-3 flex flex-wrap items-center gap-3">
        <a href="{{ route('dashboard.index') }}" class="flex items-center gap-2 font-semibold text-slate-900">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-900 text-white" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19V9m5 10V5m5 14v-7m5 7V3M2 19h20"/>
                </svg>
            </span>
            Analisis Penjualan
        </a>
        <button id="btn-back" type="button"
                class="hidden items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300 text-sm hover:bg-slate-50">
            &larr; Kembali
        </button>

        <h1 id="title" class="text-lg font-semibold">Dashboard kinerja penjualan</h1>
        <span id="badge" class="hidden text-xs font-medium px-2 py-1 rounded-full bg-blue-100 text-blue-800"></span>

        <div class="ml-auto flex flex-wrap items-center gap-2">
            <div id="overview-filters" class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Pusat Navigasi</a>
                <label class="sr-only" for="f-year">Tahun</label>
                <select id="f-year" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white">
                <option value="all">Tahun: Semua</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
                </select>

                <label class="sr-only" for="f-pay">Payment mode</label>
                <select id="f-pay" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white">
                <option value="all">Payment mode: Semua</option>
                @foreach ($paymentModes as $p)
                    <option value="{{ $p }}">{{ $p }}</option>
                @endforeach
                </select>
            </div>
            <a href="{{ route('etl.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">ETL</a>
            <a href="{{ route('transactions.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Transaksi</a>
            <span class="hidden text-sm text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
            <x-theme-toggle />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Keluar</button>
            </form>
        </div>
    </header>

    <nav class="flex gap-2" aria-label="Navigasi dashboard">
        <button id="tab-overview" type="button" class="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white">
            Overview
        </button>
        <button id="tab-what-if" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">
            What-If Analysis
        </button>
    </nav>

    <div id="overview-content" class="space-y-4">
    {{-- ===================== KPI ===================== --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([
            ['kpi-revenue', 'Total pendapatan', 'border-blue-200 bg-blue-50', 'text-blue-700', 'text-blue-950'],
            ['kpi-profit',  'Total keuntungan', 'border-emerald-200 bg-emerald-50', 'text-emerald-700', 'text-emerald-950'],
            ['kpi-qty',     'Total produk (QTY)', 'border-amber-200 bg-amber-50', 'text-amber-700', 'text-amber-950'],
            ['kpi-margin',  'Profit margin', 'border-violet-200 bg-violet-50', 'text-violet-700', 'text-violet-950'],
        ] as [$id, $label, $cardColor, $labelColor, $valueColor])
            <div class="rounded-xl border px-4 py-3 {{ $cardColor }}">
                <p class="text-sm font-medium {{ $labelColor }}">{{ $label }}</p>
                <p id="{{ $id }}" class="mt-1 text-xl font-semibold tabular-nums {{ $valueColor }}">-</p>
            </div>
        @endforeach
    </section>

    <section id="dashboard-insight" class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="font-semibold">Insight &amp; Rekomendasi</h2>
        <div class="mt-3 grid gap-3 md:grid-cols-2">
            <div class="rounded-lg bg-blue-50 p-4">
                <p id="overview-insight-title" class="font-semibold text-blue-900">Ringkasan data</p>
                <ul id="overview-insight-list" class="mt-2 space-y-1 text-sm leading-6 text-slate-700"></ul>
            </div>
            <div class="rounded-lg bg-emerald-50 p-4">
                <p class="font-semibold text-emerald-900">Rekomendasi</p>
                <p id="overview-recommendation" class="mt-2 text-sm leading-6 text-slate-700"></p>
            </div>
        </div>
    </section>

    {{-- ===================== GRID 2x2 ===================== --}}
    <section id="grid" class="grid grid-cols-1 lg:grid-cols-5 gap-4">

        {{-- Kiri-atas: Line chart --}}
        <div class="lg:col-span-3 bg-white rounded-xl border border-slate-200 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                <h2 id="t-line" class="font-semibold">Penjualan &amp; profit per tahun</h2>
                <div class="flex items-center gap-3">
                    <button id="btn-line-back" type="button"
                            class="hidden text-xs px-2 py-1 rounded border border-slate-300 hover:bg-slate-50">&larr; Semua tahun</button>
                    <span class="text-xs text-slate-500">&mdash; Penjualan &nbsp; - - Profit</span>
                </div>
            </div>
            <p id="line-hint" class="text-xs text-blue-700 mb-1">Klik titik tahun untuk melihat rincian per bulan</p>
            <div class="chart-box"><canvas id="c-line"></canvas></div>
        </div>

        {{-- Kanan-atas: Bar penjualan --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-4">
            <h2 id="t-bar" class="font-semibold mb-1">Penjualan per kategori</h2>
            <p id="hint" class="text-xs text-blue-700 mb-1">Klik batang untuk drill down ke sub-kategori</p>
            <div class="chart-box"><canvas id="c-bar"></canvas></div>
        </div>

        {{-- Kiri-bawah: Peta --}}
        <div class="lg:col-span-3 bg-white rounded-xl border border-slate-200 p-4">
            <h2 id="t-map" class="font-semibold mb-1">Penjualan per wilayah (state)</h2>
            <p id="map-hint" class="text-xs text-blue-700 mb-2">Klik lingkaran state untuk melihat rincian kotanya</p>
            <div id="map"></div>

            {{-- Panel rincian city (muncul setelah klik state) --}}
            <div id="state-panel" class="hidden mt-3">
                <div class="flex items-center justify-between mb-2">
                    <h3 id="state-title" class="text-sm font-semibold"></h3>
                    <button id="btn-state-back" type="button"
                            class="text-xs px-2 py-1 rounded border border-slate-300 hover:bg-slate-50">&larr; Semua state</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-200">
                                <th class="py-1 pr-2 font-medium">Kota</th>
                                <th class="py-1 px-2 font-medium text-right">Penjualan</th>
                                <th class="py-1 px-2 font-medium text-right">Profit</th>
                                <th class="py-1 pl-2 font-medium text-right">QTY</th>
                            </tr>
                        </thead>
                        <tbody id="state-rows" class="tabular-nums"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Kanan-bawah: Bar QTY --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-4">
            <h2 id="t-qty" class="font-semibold mb-2">QTY per kategori</h2>
            <div class="chart-box"><canvas id="c-qty"></canvas></div>
        </div>
    </section>
    </div>

    <section id="what-if-content" class="hidden grid-cols-1 gap-4 lg:grid-cols-[250px_minmax(0,1fr)]">
        <aside class="space-y-4 rounded-xl border border-slate-200 bg-slate-900 p-4 text-white">
            <div>
                <h2 class="font-semibold">Filter Analisis</h2>
                <p class="mt-1 text-xs text-slate-300">Pilih rentang data yang ingin disimulasikan.</p>
            </div>

            @foreach ([
                ['wi-year', 'Tahun', $years],
                ['wi-month', 'Bulan', []],
                ['wi-category', 'Category', $categories],
                ['wi-sub-category', 'Sub-Category', []],
                ['wi-state', 'State / Wilayah', $states],
                ['wi-city', 'City', []],
            ] as [$id, $label, $options])
                <label class="block text-xs font-medium text-slate-200" for="{{ $id }}">
                    {{ $label }}
                    <select id="{{ $id }}" class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-sm text-white">
                        <option value="all">Semua</option>
                        @foreach ($options as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
            @endforeach

            <button id="wi-reset" type="button" class="w-full rounded-lg border border-slate-500 px-3 py-2 text-sm hover:bg-slate-800">
                Reset Filter
            </button>
        </aside>

        <div class="space-y-4">
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold">What-If Analysis</h1>
                        <p class="mt-1 text-sm text-slate-500">Simulasikan perubahan nilai untuk melihat dampaknya terhadap kinerja penjualan.</p>
                    </div>
                    <p class="max-w-xs rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-800">
                        Proyeksi dihitung dari data sesuai filter yang dipilih.
                    </p>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_auto] md:items-end">
                    <label class="text-sm font-medium" for="wi-metric">
                        Pilih Variabel What-If
                        <select id="wi-metric" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                            <option value="amount">Pendapatan (Amount)</option>
                            <option value="profit">Keuntungan (Profit)</option>
                            <option value="quantity">Quantity</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium" for="wi-change">
                        Masukkan Perubahan
                        <span class="mt-1 flex rounded-lg border border-slate-300">
                            <input id="wi-change" type="number" value="10" min="-100" max="1000" step="0.1"
                                   class="min-w-0 flex-1 rounded-l-lg px-3 py-2.5" aria-describedby="wi-change-hint">
                            <span class="border-l border-slate-300 px-3 py-2.5 text-slate-500">%</span>
                        </span>
                    </label>
                    <button id="wi-run" type="button" class="rounded-lg bg-blue-900 px-5 py-2.5 font-medium text-white hover:bg-blue-800">
                        Terapkan Simulasi
                    </button>
                </div>
                <p id="wi-change-hint" class="mt-2 text-xs text-slate-500">Perubahan dapat diatur dari -100% hingga 1000%.</p>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan simulasi">
                @foreach ([
                    ['wi-card-one-label', 'wi-card-one-value'],
                    ['wi-card-two-label', 'wi-card-two-value'],
                    ['wi-card-three-label', 'wi-card-three-value'],
                    ['wi-card-four-label', 'wi-card-four-value'],
                ] as [$labelId, $valueId])
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <p id="{{ $labelId }}" class="text-sm text-slate-500">-</p>
                        <p id="{{ $valueId }}" class="mt-2 text-lg font-semibold tabular-nums">-</p>
                    </article>
                @endforeach
            </section>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(280px,0.8fr)]">
                <section class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 id="wi-chart-title" class="font-semibold">Perbandingan Aktual dan Proyeksi</h2>
                        <div class="flex items-center gap-3 text-xs text-slate-500">
                            <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-blue-900"></span>Aktual</span>
                            <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-sky-400"></span>Proyeksi</span>
                        </div>
                    </div>
                    <div class="chart-box mt-3"><canvas id="wi-chart"></canvas></div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-4">
                    <h2 class="font-semibold">Insight &amp; Rekomendasi</h2>
                    <div class="mt-3 rounded-lg bg-blue-50 p-4">
                        <p id="wi-insight-title" class="font-semibold text-blue-900">Hasil simulasi</p>
                        <p id="wi-insight" class="mt-2 text-sm leading-6 text-slate-700">Pilih filter dan terapkan simulasi untuk melihat proyeksi.</p>
                    </div>
                    <div class="mt-3 rounded-lg bg-emerald-50 p-4">
                        <p class="font-semibold text-emerald-900">Rekomendasi</p>
                        <p id="wi-recommendation" class="mt-2 text-sm leading-6 text-slate-700">Gunakan hasil proyeksi sebagai bahan evaluasi strategi penjualan.</p>
                    </div>
                </section>
            </div>
        </div>
    </section>

    <p id="error" class="hidden text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2"></p>
</div>

<script>
function initializeDashboardAnalysis() {
/* ================================================================
 *  Konfigurasi & helper
 * ================================================================ */
const ROUTES = {
    l1: @json(route('dashboard.level1')),
    l2: @json(route('dashboard.level2')),
    st: @json(route('dashboard.state')),
    mo: @json(route('dashboard.months')),
    whatIf: @json(route('dashboard.what-if')),
};

const SUB_CATEGORIES = @json($subCategoriesByCategory);
const CITIES_BY_STATE = @json($citiesByState);
const MONTHS_BY_YEAR = @json($monthsByYear);
const state = { level: 1, category: null, region: null, trendYear: null };
let requestId = 0;
let stateReq = 0;
let monthReq = 0;
let whatIfRequestId = 0;
let isWhatIfActive = false;
let lastData = null;
const esc = (t) => String(t).replace(/[&<>"']/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

const $   = (id) => document.getElementById(id);
const rp  = (v) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v);
const num = (v) => new Intl.NumberFormat('en-US').format(v);
const pct = (v) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(v) + ' %';
const short = (v) => {
    const abs = Math.abs(v);
    if (abs >= 1e9) return '$' + (v / 1e9).toFixed(1).replace(/\.0$/, '') + 'B';
    if (abs >= 1e6) return '$' + (v / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
    if (abs >= 1e3) return '$' + (v / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
    return rp(v);
};

const COLOR = { sales: '#1d4ed8', profit: '#0f766e', bar: '#64748b', barActive: '#1d4ed8', qty: '#94a3b8' };

/* ================================================================
 *  Inisialisasi Chart.js
 * ================================================================ */
const moneyTooltip = { callbacks: { label: (c) => `${c.dataset.label}: ${rp(c.parsed.y)}` } };

const lineChart = new Chart($('c-line'), {
    type: 'line',
    data: { labels: [], datasets: [
        { label: 'Penjualan', data: [], borderColor: COLOR.sales,  backgroundColor: COLOR.sales,  borderWidth: 2.5, tension: .25, pointRadius: 3 },
        { label: 'Profit',    data: [], borderColor: COLOR.profit, backgroundColor: COLOR.profit, borderWidth: 2.5, tension: .25, pointRadius: 3, borderDash: [6, 5] },
    ]},
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        onHover: (e, els) => { e.native.target.style.cursor = (state.level === 1 && !state.trendYear && els.length) ? 'pointer' : 'default'; },
        // Klik titik tahun -> tampilkan tren per bulan untuk tahun itu
        onClick: (evt, els, chart) => {
            if (state.level !== 1 || state.trendYear) return;
            const hit = chart.getElementsAtEventForMode(evt, 'index', { intersect: false }, true);
            if (hit.length) selectYear(chart.data.labels[hit[0].index]);
        },
        plugins: { legend: { display: false }, tooltip: moneyTooltip },
        scales: {
            x: { ticks: { maxRotation: 60, autoSkip: true, maxTicksLimit: 12 } },
            y: { beginAtZero: true, ticks: { callback: short } },
        },
    },
});

const barChart = new Chart($('c-bar'), {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Penjualan', data: [], backgroundColor: COLOR.bar, borderRadius: 4 }] },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: moneyTooltip },
        scales: { y: { beginAtZero: true, ticks: { callback: short } } },
        onHover: (e, els) => { e.native.target.style.cursor = (state.level === 1 && els.length) ? 'pointer' : 'default'; },
        // Klik batang kategori -> drill down ke Level 2
        onClick: (evt, els, chart) => {
            if (state.level !== 1 || !els.length) return;
            drillDown(chart.data.labels[els[0].index]);
        },
    },
});

const qtyChart = new Chart($('c-qty'), {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'QTY', data: [], backgroundColor: COLOR.qty, borderRadius: 4 }] },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => `QTY: ${num(c.parsed.y)}` } } },
        scales: { y: { beginAtZero: true, ticks: { callback: (v) => num(v) } } },
    },
});

const whatIfChart = new Chart($('wi-chart'), {
    type: 'bar',
    data: { labels: [], datasets: [
        { label: 'Aktual', data: [], backgroundColor: '#1e3a8a', borderRadius: 4 },
        { label: 'Proyeksi', data: [], backgroundColor: '#60a5fa', borderRadius: 4 },
    ]},
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (context) => {
                const metric = $('wi-metric').value;
                const value = metric === 'quantity' ? num(context.parsed.y) : rp(context.parsed.y);
                return `${context.dataset.label}: ${value}`;
            }}},
        },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { callback: (value) => $('wi-metric').value === 'quantity' ? num(value) : short(value) } },
        },
    },
});

function updateChartsTheme(dark) {
    const color = dark ? '#cbd5e1' : '#475569';
    const gridColor = dark ? 'rgba(148, 163, 184, 0.18)' : 'rgba(100, 116, 139, 0.16)';

    Chart.defaults.color = color;
    [lineChart, barChart, qtyChart, whatIfChart].forEach((chart) => {
        Object.values(chart.options.scales || {}).forEach((scale) => {
            scale.ticks = { ...scale.ticks, color };
            scale.grid = { ...scale.grid, color: gridColor };
            scale.border = { ...scale.border, color: gridColor };
        });
        chart.options.plugins.legend.labels.color = color;
        chart.options.plugins.tooltip = {
            ...chart.options.plugins.tooltip,
            backgroundColor: dark ? '#0f172a' : '#ffffff',
            titleColor: dark ? '#f1f5f9' : '#0f172a',
            bodyColor: dark ? '#e2e8f0' : '#334155',
            borderColor: dark ? '#475569' : '#e2e8f0',
            borderWidth: 1,
        };
        chart.update('none');
    });
}

updateChartsTheme(document.documentElement.classList.contains('dark'));
window.addEventListener('dashboardThemeChanged', (event) => updateChartsTheme(event.detail.dark));

/* ================================================================
 *  Leaflet
 * ================================================================ */
const map = L.map('map', { scrollWheelZoom: false }).setView([37.5, -96], 4);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18, attribution: '&copy; OpenStreetMap contributors',
}).addTo(map);
const markerLayer = L.layerGroup().addTo(map);

function renderMap(points, opts = {}) {
    const { onClick = null, small = false } = opts;
    markerLayer.clearLayers();
    if (!points.length) return;

    const max = Math.max(...points.map((p) => p.amount)) || 1;
    const [rMin, rMax] = small ? [7, 28] : [10, 42];

    points.forEach((p) => {
        const radius = rMin + (rMax - rMin) * Math.sqrt(p.amount / max);
        const marker = L.circleMarker([p.lat, p.lng], {
            radius, color: COLOR.sales, weight: 1.5, fillColor: COLOR.sales, fillOpacity: .45,
            className: onClick ? 'state-marker' : '',
        })
        .bindTooltip(
            `<strong>${esc(p.name)}</strong><br>Penjualan: ${rp(p.amount)}<br>Profit: ${rp(p.profit)}<br>QTY: ${num(p.qty)}`,
            { direction: 'top' }
        )
        .addTo(markerLayer);

        if (onClick) marker.on('click', () => onClick(p));
    });

    map.invalidateSize();
    map.fitBounds(L.latLngBounds(points.map((p) => [p.lat, p.lng])), { padding: [40, 40], maxZoom: 7 });
}

function applyTrend(t) {
    lineChart.data.labels = t.labels;
    lineChart.data.datasets[0].data = t.sales;
    lineChart.data.datasets[1].data = t.profit;
    lineChart.update();
}

function selectYear(year) {
    state.trendYear = year;
    renderLabels();
    loadMonths();
}

function clearYear() {
    state.trendYear = null;
    monthReq++;                       // batalkan permintaan yang masih berjalan
    renderLabels();
    if (lastData) applyTrend(lastData.trend);
}

async function loadMonths() {
    const id = ++monthReq;
    const year = state.trendYear;
    const params = new URLSearchParams({ year, payment_mode: $('f-pay').value });

    try {
        const res = await fetch(ROUTES.mo + '?' + params, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const d = await res.json();
        if (id !== monthReq || state.trendYear !== year) return;   // respons usang
        applyTrend(d.trend);
    } catch (e) {
        $('error').textContent = 'Gagal memuat data bulanan: ' + e.message;
        $('error').classList.remove('hidden');
    }
}

// Klik state di peta -> tampilkan city di state itu
const stateClick = (p) => selectState(p.name);

function selectState(name) {
    state.region = name;
    renderLabels();
    loadState();
}

function clearState() {
    state.region = null;
    stateReq++;                       // batalkan permintaan yang masih berjalan
    renderLabels();
    if (lastData) renderMap(lastData.map, { onClick: stateClick });
}

async function loadState() {
    const id = ++stateReq;
    const name = state.region;
    const params = new URLSearchParams({
        state: name,
        year: $('f-year').value,
        payment_mode: $('f-pay').value,
    });

    try {
        const res = await fetch(ROUTES.st + '?' + params, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const d = await res.json();
        if (id !== stateReq || state.region !== name) return;   // respons usang
        renderCities(d);
    } catch (e) {
        $('error').textContent = 'Gagal memuat data kota: ' + e.message;
        $('error').classList.remove('hidden');
    }
}

function renderCities(d) {
    renderMap(d.cities, { small: true });

    $('state-title').textContent =
        `${d.state}: ${rp(d.kpi.revenue)} penjualan, ${rp(d.kpi.profit)} profit, ${num(d.kpi.qty)} QTY`;

    $('state-rows').innerHTML = d.cities.length
        ? d.cities.map((c) => `
            <tr class="border-b border-slate-100">
                <td class="py-1 pr-2">${esc(c.name)}</td>
                <td class="py-1 px-2 text-right">${rp(c.amount)}</td>
                <td class="py-1 px-2 text-right">${rp(c.profit)}</td>
                <td class="py-1 pl-2 text-right">${num(c.qty)}</td>
            </tr>`).join('')
        : '<tr><td colspan="4" class="py-2 text-slate-500">Tidak ada data untuk filter ini.</td></tr>';
}

/* ================================================================
 *  Render
 * ================================================================ */
function renderKpi(k) {
    $('kpi-revenue').textContent = rp(k.revenue);
    $('kpi-profit').textContent  = rp(k.profit);
    $('kpi-qty').textContent     = num(k.qty);
    $('kpi-margin').textContent  = pct(k.margin);
}

function renderOverviewInsight(insight) {
    if (!insight || !insight.transactions) {
        $('overview-insight-title').textContent = 'Belum ada data untuk dianalisis';
        $('overview-insight-list').innerHTML = '<li>Belum ada data untuk dianalisis</li>';
        $('overview-recommendation').textContent = 'Tambahkan atau unggah data transaksi untuk memulai analisis.';
        return;
    }

    const items = [
        `Total penjualan ${rp(insight.totalRevenue)} dari ${num(insight.transactions)} transaksi.`,
        insight.trend
            ? `Pendapatan periode terakhir ${insight.trend.direction} ${pct(insight.trend.percent)} dibanding periode sebelumnya.`
            : 'Tren periode sebelumnya belum tersedia untuk perbandingan.',
        insight.topCategory
            ? `Kategori teratas: ${insight.topCategory} dengan kontribusi ${rp(insight.topCategoryRevenue)}.`
            : 'Kategori teratas belum tersedia.',
    ];
    $('overview-insight-title').textContent = 'Ringkasan hasil data';
    $('overview-insight-list').innerHTML = items.map((item) => `<li>• ${esc(item)}</li>`).join('');
    $('overview-recommendation').textContent = insight.topCategory
        ? `Fokuskan strategi pada ${insight.topCategory} dan pertahankan kontribusi penjualannya.`
        : 'Tambahkan variasi kategori untuk memperluas analisis penjualan.';
}

function renderLabels() {
    const l1 = state.level === 1;
    const pageTitle = isWhatIfActive ? 'What-If Analysis' : (l1 ? 'Dashboard kinerja penjualan' : 'Drill down: sub-kategori');
    $('title').textContent = pageTitle;
    document.title = `${pageTitle} | Analisis Penjualan`;
    $('badge').textContent  = l1 ? '' : 'Kategori: ' + state.category;
    $('badge').classList.toggle('hidden', isWhatIfActive || l1);
    $('btn-back').classList.toggle('hidden', isWhatIfActive || l1);
    $('btn-back').classList.toggle('inline-flex', !isWhatIfActive && !l1);
    $('hint').classList.toggle('hidden', !l1);

    $('t-line').textContent = !l1 ? 'Penjualan & profit per bulan'
                            : (state.trendYear ? 'Penjualan & profit per bulan: ' + state.trendYear : 'Penjualan & profit per tahun');
    $('line-hint').classList.toggle('hidden', !l1 || !!state.trendYear);
    $('btn-line-back').classList.toggle('hidden', !(l1 && state.trendYear));
    $('t-bar').textContent  = l1 ? 'Penjualan per kategori'       : 'Penjualan per sub-kategori';
    $('t-qty').textContent  = l1 ? 'QTY per kategori'             : 'QTY per sub-kategori';
    const region = l1 && state.region;
    $('t-map').textContent  = !l1 ? 'Penjualan per wilayah (city)'
                            : (region ? 'Penjualan per wilayah (city): ' + state.region : 'Penjualan per wilayah (state)');
    $('map-hint').classList.toggle('hidden', !l1 || !!state.region);
    $('state-panel').classList.toggle('hidden', !region);
}

function render(d) {
    renderLabels();
    renderKpi(d.kpi);
    if (d.insight) renderOverviewInsight(d.insight);

    if (state.level === 1 && state.trendYear) {
        loadMonths();                                 // tetap di tampilan bulan saat filter berubah
    } else {
        applyTrend(d.trend);
    }

    barChart.data.labels = d.bar.labels;
    barChart.data.datasets[0].data = d.bar.amount;
    barChart.data.datasets[0].backgroundColor = state.level === 1 ? COLOR.bar : COLOR.barActive;
    barChart.update();

    qtyChart.data.labels = d.bar.labels;
    qtyChart.data.datasets[0].data = d.bar.qty;
    qtyChart.update();

    lastData = d;
    if (state.level === 1 && state.region) {
        loadState();                                  // tetap di rincian state saat filter berubah
    } else {
        renderMap(d.map, state.level === 1 ? { onClick: stateClick } : { small: true });
    }
}

/* ================================================================
 *  Ambil data (AJAX, tanpa refresh halaman)
 * ================================================================ */
async function load() {
    const id = ++requestId;
    const params = new URLSearchParams({
        year: $('f-year').value,
        payment_mode: $('f-pay').value,
    });
    if (state.level === 2) params.set('category', state.category);

    $('grid').classList.add('loading');
    $('error').classList.add('hidden');

    try {
        const res = await fetch((state.level === 1 ? ROUTES.l1 : ROUTES.l2) + '?' + params, {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (id !== requestId) return; // abaikan respons usang
        render(data);
    } catch (e) {
        $('error').textContent = 'Gagal memuat data: ' + e.message;
        $('error').classList.remove('hidden');
    } finally {
        if (id === requestId) $('grid').classList.remove('loading');
    }
}

function drillDown(category) {
    state.level = 2;
    state.category = category;
    state.region = null;
    state.trendYear = null;
    load();
}

function backToLevel1() {
    state.level = 1;
    state.category = null;
    state.region = null;
    state.trendYear = null;
    load();
}

/* ================================================================
 *  Event
 * ================================================================ */
$('btn-back').addEventListener('click', backToLevel1);
$('btn-line-back').addEventListener('click', clearYear);
$('btn-state-back').addEventListener('click', clearState);
$('f-year').addEventListener('change', load);   // filter berlaku di level yang sedang aktif
$('f-pay').addEventListener('change', load);

const whatIfFilters = ['wi-year', 'wi-month', 'wi-category', 'wi-sub-category', 'wi-state', 'wi-city'];
const monthNames = {
    '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April',
    '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Agustus',
    '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember',
};

function updateOptions(select, values, format = (value) => value) {
    const selected = select.value;
    select.replaceChildren(new Option('Semua', 'all'));
    values.forEach((value) => select.add(new Option(format(value), value)));
    select.value = values.includes(selected) ? selected : 'all';
}

function setWhatIfTab(active) {
    isWhatIfActive = active;
    $('overview-content').classList.toggle('hidden', active);
    $('what-if-content').classList.toggle('hidden', !active);
    $('what-if-content').classList.toggle('grid', active);
    $('overview-filters').classList.toggle('hidden', active);
    $('btn-back').classList.toggle('hidden', active || state.level === 1);
    $('badge').classList.toggle('hidden', active || state.level === 1);
    $('tab-overview').className = active
        ? 'rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50'
        : 'rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white';
    $('tab-what-if').className = active
        ? 'rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white'
        : 'rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50';
    $('title').textContent = active ? 'What-If Analysis' : 'Dashboard kinerja penjualan';
    if (active) {
        whatIfChart.resize();
        if (!$('wi-card-one-value').textContent || $('wi-card-one-value').textContent === '-') runWhatIf();
    } else {
        renderLabels();
    }
}

function selectedWhatIfFilters() {
    return {
        year: $('wi-year').value,
        month: $('wi-month').value,
        category: $('wi-category').value,
        sub_category: $('wi-sub-category').value,
        state: $('wi-state').value,
        city: $('wi-city').value,
    };
}

function monthsForYear(year) {
    return year === 'all'
        ? [...new Set(Object.values(MONTHS_BY_YEAR).flat())].sort()
        : (MONTHS_BY_YEAR[year] || []);
}

function subCategoriesForCategory(category) {
    return category === 'all'
        ? [...new Set(Object.values(SUB_CATEGORIES).flat())].sort()
        : (SUB_CATEGORIES[category] || []);
}

function citiesForState(stateName) {
    return stateName === 'all'
        ? [...new Set(Object.values(CITIES_BY_STATE).flat())].sort()
        : (CITIES_BY_STATE[stateName] || []);
}

function renderWhatIf(data) {
    const metric = data.metric;
    const actual = data.actual;
    const projected = data.projected;
    const money = (value) => rp(value);
    const quantity = (value) => `${num(Math.round(value))} unit`;
    const cards = metric === 'quantity'
        ? [
            ['Quantity Aktual', quantity(actual.quantity)],
            ['Quantity Proyeksi', quantity(projected.quantity)],
            ['Pendapatan Aktual', money(actual.amount)],
            ['Pendapatan Proyeksi', money(projected.amount)],
        ]
        : [
            ['Pendapatan Aktual', money(actual.amount)],
            ['Pendapatan Proyeksi', money(projected.amount)],
            ['Keuntungan Aktual', money(actual.profit)],
            ['Keuntungan Proyeksi', money(projected.profit)],
        ];

    cards.forEach(([label, value], index) => {
        $(`wi-card-${['one', 'two', 'three', 'four'][index]}-label`).textContent = label;
        $(`wi-card-${['one', 'two', 'three', 'four'][index]}-value`).textContent = value;
    });

    const chartMetrics = metric === 'quantity'
        ? [['Quantity (Qty)', actual.quantity, projected.quantity]]
        : [
            ['Pendapatan (Amount)', actual.amount, projected.amount],
            ['Keuntungan (Profit)', actual.profit, projected.profit],
        ];
    whatIfChart.data.labels = chartMetrics.map(([label]) => label);
    whatIfChart.data.datasets[0].data = chartMetrics.map(([, value]) => value);
    whatIfChart.data.datasets[1].data = chartMetrics.map(([, , value]) => value);
    whatIfChart.update();
    $('wi-chart-title').textContent = `Perbandingan ${metric === 'quantity' ? 'Quantity' : 'Pendapatan dan Keuntungan'}`;

    const metricName = { amount: 'pendapatan', profit: 'keuntungan', quantity: 'quantity' }[metric];
    const verb = data.change > 0 ? 'meningkat' : (data.change < 0 ? 'menurun' : 'tidak berubah');
    const formattedValue = metric === 'quantity' ? quantity(projected.quantity) : money(projected[metric]);
    $('wi-insight-title').textContent = `${metricName.charAt(0).toUpperCase()}${metricName.slice(1)} ${verb} ${pct(Math.abs(data.change))}`;
    $('wi-insight').textContent = `Dengan perubahan ${pct(data.change)}, ${metricName} diproyeksikan menjadi ${formattedValue}. Pendapatan: ${money(projected.amount)} dan keuntungan: ${money(projected.profit)}.`;
    $('wi-recommendation').textContent = data.change > 0
        ? 'Evaluasi kapasitas dan strategi yang diperlukan untuk mencapai target proyeksi ini.'
        : 'Tinjau faktor yang mendorong penurunan dan siapkan langkah mitigasi untuk menjaga kinerja.';
}

async function runWhatIf() {
    const changeInput = $('wi-change');
    const change = Number(changeInput.value);
    if (!Number.isFinite(change) || change < -100 || change > 1000) {
        $('error').textContent = 'Nilai perubahan harus berada di antara -100% dan 1000%.';
        $('error').classList.remove('hidden');
        changeInput.focus();
        return;
    }

    const id = ++whatIfRequestId;
    const params = new URLSearchParams();
    Object.entries(selectedWhatIfFilters()).forEach(([key, value]) => {
        if (value !== 'all') params.set(key, value);
    });
    params.set('metric', $('wi-metric').value);
    params.set('change', String(change));
    $('error').classList.add('hidden');
    $('wi-run').disabled = true;
    $('wi-run').classList.add('opacity-60', 'cursor-wait');

    try {
        const response = await fetch(`${ROUTES.whatIf}?${params}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (id !== whatIfRequestId) return;
        renderWhatIf(data);
    } catch (error) {
        if (id !== whatIfRequestId) return;
        $('error').textContent = 'Gagal memuat simulasi: ' + error.message;
        $('error').classList.remove('hidden');
    } finally {
        if (id === whatIfRequestId) {
            $('wi-run').disabled = false;
            $('wi-run').classList.remove('opacity-60', 'cursor-wait');
        }
    }
}

$('tab-overview').addEventListener('click', () => setWhatIfTab(false));
$('tab-what-if').addEventListener('click', () => setWhatIfTab(true));
$('wi-run').addEventListener('click', runWhatIf);
$('wi-change').addEventListener('change', runWhatIf);
$('wi-metric').addEventListener('change', runWhatIf);

$('wi-category').addEventListener('change', () => {
    updateOptions($('wi-sub-category'), subCategoriesForCategory($('wi-category').value));
    runWhatIf();
});
$('wi-state').addEventListener('change', () => {
    updateOptions($('wi-city'), citiesForState($('wi-state').value));
    runWhatIf();
});
$('wi-year').addEventListener('change', () => {
    updateOptions($('wi-month'), monthsForYear($('wi-year').value), (month) => monthNames[month] || month);
    runWhatIf();
});
$('wi-month').addEventListener('change', runWhatIf);
$('wi-sub-category').addEventListener('change', runWhatIf);
$('wi-city').addEventListener('change', runWhatIf);
$('wi-reset').addEventListener('click', () => {
    whatIfFilters.forEach((id) => { $(id).value = 'all'; });
    updateOptions($('wi-month'), monthsForYear('all'), (month) => monthNames[month] || month);
    updateOptions($('wi-sub-category'), subCategoriesForCategory('all'));
    updateOptions($('wi-city'), citiesForState('all'));
    $('wi-metric').value = 'amount';
    $('wi-change').value = 10;
    runWhatIf();
});

updateOptions($('wi-month'), monthsForYear('all'), (month) => monthNames[month] || month);
updateOptions($('wi-sub-category'), subCategoriesForCategory('all'));
updateOptions($('wi-city'), citiesForState('all'));
load();
}

if (window.Chart && window.L) {
    initializeDashboardAnalysis();
} else {
    window.addEventListener('dashboardLibrariesReady', initializeDashboardAnalysis, { once: true });
}
</script>
</body>
</html>
