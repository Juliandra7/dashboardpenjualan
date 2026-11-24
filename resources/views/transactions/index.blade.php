@extends('layouts.app')

@section('title', 'Data Transaksi | Analisis Penjualan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-violet-800">Penelusuran data</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Data Transaksi</h1>
            <p class="mt-2 text-slate-600">Cari transaksi, gunakan filter, lalu pilih baris untuk melihat rincian.</p>
        </div>
        <a href="{{ route('etl.index') }}" class="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">ETL</a>
    </div>

    <form method="GET" action="{{ route('transactions.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-[minmax(0,1fr)_200px_200px_auto_auto] md:items-end">
        <label class="text-sm font-medium text-slate-700" for="search">
            Cari transaksi
            <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Order ID, pelanggan, atau kota"
                   class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label class="text-sm font-medium text-slate-700" for="category">
            Category
            <select id="category" name="category" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                <option value="">Semua</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-medium text-slate-700" for="state">
            State / Wilayah
            <select id="state" name="state" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                <option value="">Semua</option>
                @foreach ($states as $state)
                    <option value="{{ $state }}" @selected(($filters['state'] ?? '') === $state)>{{ $state }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
        <a href="{{ route('transactions.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-center text-sm font-medium hover:bg-slate-50">Reset</a>
    </form>

    <div class="mt-5"><x-transaction-table :sales="$sales" /></div>
@endsection
