@extends('layouts.app')

@section('title', 'Beranda | Analisis Penjualan')

@section('content')
    <section class="mx-auto max-w-4xl py-10">
        <header class="text-center">
            <p class="text-sm font-semibold text-blue-800">Beranda</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Selamat datang, {{ auth()->user()->name }}</h1>
        </header>
        <div class="mt-8 grid gap-5 md:grid-cols-2">
            <a href="{{ route('dashboard.analysis') }}" class="group rounded-2xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:border-blue-300 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-blue-100" aria-label="Buka Dashboard Overview">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-900" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M7 14l4-4 3 3 6-7"/></svg>
                </span>
                <h2 class="mt-5 text-xl font-semibold">Dashboard Overview</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Lihat ringkasan kinerja, tren, dan insight penjualan.</p>
            </a>
            <a href="{{ route('etl.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-emerald-100" aria-label="Buka ETL">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v4h14v-4"/></svg>
                </span>
                <h2 class="mt-5 text-xl font-semibold">ETL</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Tambah, unggah, atau ganti data transaksi melalui proses ETL.</p>
            </a>
        </div>
    </section>
@endsection
