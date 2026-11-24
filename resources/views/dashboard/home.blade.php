@extends('layouts.app')

@section('title', 'Dashboard | Analisis Penjualan')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white px-5 py-5 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-blue-800">Pusat navigasi</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-2 text-slate-600">Pilih area kerja untuk menganalisis, mengelola, atau menelusuri data transaksi.</p>
        </div>
            <p class="shrink-0 rounded-full bg-blue-50 px-4 py-2 text-sm text-blue-900">Halo, {{ auth()->user()->name }}</p>
        </div>
    </section>

    <section class="mt-7 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['Total transaksi', number_format($summary->transactions, 0, ',', '.'), 'border-blue-200 bg-blue-50', 'text-blue-700', 'text-blue-950'],
            ['Pendapatan', \App\Support\Currency::format($summary->revenue), 'border-emerald-200 bg-emerald-50', 'text-emerald-700', 'text-emerald-950'],
            ['Keuntungan', \App\Support\Currency::format($summary->profit), 'border-violet-200 bg-violet-50', 'text-violet-700', 'text-violet-950'],
        ] as [$label, $value, $cardColor, $labelColor, $valueColor])
            <article class="rounded-2xl border p-5 {{ $cardColor }}">
                <p class="text-sm font-medium {{ $labelColor }}">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold tabular-nums {{ $valueColor }}">{{ $value }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 lg:grid-cols-[1.4fr_0.8fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <div><h2 class="font-semibold">Tren Pendapatan Bulanan</h2><p class="mt-1 text-sm text-slate-500">12 periode terakhir dari data transaksi</p></div>
                <span class="rounded-lg bg-blue-50 px-3 py-1 text-xs font-medium text-blue-900">Amount</span>
            </div>
            @php($maxMonthlyAmount = max((float) $monthlySales->max('amount'), 1))
            @if ($monthlySales->isNotEmpty())
                <div class="mt-6 flex h-44 items-end gap-2 border-b border-slate-200 pb-1 sm:gap-3" role="img" aria-label="Grafik batang pendapatan bulanan">
                    @foreach ($monthlySales as $month)
                        <div class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2">
                            <span class="hidden text-[10px] text-slate-500 group-hover:block">{{ \App\Support\Currency::short($month->amount) }}</span>
                            <div class="w-full rounded-t-md bg-blue-700 transition-colors group-hover:bg-emerald-600"
                                 style="height: {{ max(4, (float) $month->amount / $maxMonthlyAmount * 100) }}%"
                                 title="{{ $month->period }}: {{ \App\Support\Currency::format($month->amount) }}"></div>
                            <span class="truncate text-[10px] text-slate-500">{{ substr($month->period, 5) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-6 rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada data untuk ditampilkan.</p>
            @endif
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Kategori Teratas</h2>
            <p class="mt-1 text-sm text-slate-500">Berdasarkan total pendapatan</p>
            @php($maxCategoryAmount = max((float) $topCategories->max('amount'), 1))
            <ul class="mt-5 space-y-4">
                @forelse ($topCategories as $category)
                    <li>
                        <div class="mb-1 flex justify-between gap-3 text-sm">
                            <span class="truncate font-medium">{{ $category->category }}</span>
                            <span class="shrink-0 text-slate-500">{{ \App\Support\Currency::format($category->amount) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-violet-600" style="width: {{ max(1, (float) $category->amount / $maxCategoryAmount * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="rounded-lg bg-slate-50 p-5 text-sm text-slate-500">Belum ada data untuk ditampilkan.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <a href="{{ route('dashboard.analysis') }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-blue-300 hover:shadow-lg">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-900" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M7 14l4-4 3 3 6-7"/></svg>
            </span>
            <h2 class="mt-5 text-lg font-semibold">Dashboard Kinerja Penjualan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Ringkasan KPI, tren, kategori, wilayah, drill-down, dan simulasi What-If.</p>
            <span class="mt-5 inline-flex font-medium text-blue-900 group-hover:underline">Buka analisis <span aria-hidden="true" class="ml-2">→</span></span>
        </a>

        <a href="{{ route('etl.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v4h14v-4"/></svg>
            </span>
            <h2 class="mt-5 text-lg font-semibold">ETL &amp; Penambahan Data</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Tambahkan transaksi melalui form manual atau impor CSV dengan validasi dan preview.</p>
            <span class="mt-5 inline-flex font-medium text-emerald-800 group-hover:underline">Kelola data <span aria-hidden="true" class="ml-2">→</span></span>
        </a>

        <a href="{{ route('transactions.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-violet-300 hover:shadow-lg">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-800" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16M4 10h16M4 15h10M4 20h10M18 15v5m-2.5-2.5h5"/></svg>
            </span>
            <h2 class="mt-5 text-lg font-semibold">Data Transaksi</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Cari, filter, dan buka rincian transaksi yang sudah tersimpan.</p>
            <span class="mt-5 inline-flex font-medium text-violet-800 group-hover:underline">Lihat transaksi <span aria-hidden="true" class="ml-2">→</span></span>
        </a>
    </section>
@endsection
