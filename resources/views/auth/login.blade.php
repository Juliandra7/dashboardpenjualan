@extends('layouts.app')

@section('title', 'Masuk | Analisis Penjualan')

@section('content')
    <div class="mx-auto grid max-w-4xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm md:grid-cols-2">
        <div class="hidden bg-gradient-to-br from-slate-950 to-blue-800 p-10 text-white md:flex md:flex-col md:justify-between">
            <div>
                <p class="text-sm font-medium text-blue-200">Analisis Penjualan</p>
                <h1 class="mt-4 text-3xl font-bold">Selamat datang kembali.</h1>
                <p class="mt-3 leading-7 text-blue-100">Masuk untuk mengakses dashboard, mengelola transaksi, dan menjalankan analisis.</p>
            </div>
            <p class="text-sm text-blue-200">Akses hanya tersedia untuk akun yang dibuat oleh administrator.</p>
        </div>
        <div class="p-6 sm:p-10">
            <h2 class="text-2xl font-bold text-slate-900">Masuk ke akun</h2>
            <p class="mt-2 text-sm text-slate-500">Gunakan username (nama akun) atau email dan password.</p>

            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <p class="font-medium">Tidak dapat masuk.</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf
                <label class="block text-sm font-medium text-slate-700" for="identifier">
                    Username atau email
                    <input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" required autofocus autocomplete="username"
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100">
                </label>
                <label class="block text-sm font-medium text-slate-700" for="password">
                    Password
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100">
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-blue-800 focus:ring-blue-700">
                    Ingat saya
                </label>
                <button type="submit" class="w-full rounded-xl bg-blue-900 px-4 py-3 font-semibold text-white hover:bg-blue-800">Masuk</button>
            </form>
        </div>
    </div>
@endsection
