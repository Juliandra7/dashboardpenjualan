@extends('layouts.app')

@section('title', 'Detail Transaksi | Analisis Penjualan')

@section('content')
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('transactions.index') }}" class="text-sm font-medium text-blue-800 hover:underline">← Kembali ke transaksi</a>
        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-start justify-between gap-3 bg-slate-950 px-6 py-5 text-white">
                <div>
                    <p class="text-sm text-slate-300">Detail transaksi</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ $sale->order_id }}</h1>
                </div>
                <span class="rounded-full bg-white/10 px-3 py-1.5 text-sm">{{ $sale->order_date->format('d F Y') }}</span>
            </div>
            <dl class="grid gap-x-8 sm:grid-cols-2">
                @foreach ([
                    ['Pelanggan', $sale->customer_name],
                    ['Kategori', $sale->category],
                    ['Sub-kategori', $sale->sub_category],
                    ['Metode pembayaran', $sale->payment_mode],
                    ['Wilayah / State', $sale->state],
                    ['Kota', $sale->city],
                    ['Periode', $sale->year_month],
                    ['Quantity', number_format($sale->quantity, 0, ',', '.')],
                    ['Pendapatan', \App\Support\Currency::format($sale->amount)],
                    ['Keuntungan', \App\Support\Currency::format($sale->profit)],
                ] as [$label, $value])
                    <div class="border-b border-slate-100 px-6 py-4">
                        <dt class="text-sm text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
@endsection
