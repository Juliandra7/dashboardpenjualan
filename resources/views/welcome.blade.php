@extends('layouts.app')

@section('title', 'Analisis Penjualan')

@section('content')
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 px-6 py-10 text-white shadow-sm sm:px-10 sm:py-14 lg:px-14">
        <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-blue-600/30 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-44 w-44 rounded-full bg-violet-600/20 blur-3xl"></div>

        <div class="relative grid items-center gap-10 lg:grid-cols-[1.15fr_0.85fr]">
            <div>
                <p class="inline-flex rounded-full border border-blue-400/30 bg-blue-400/10 px-3 py-1 text-sm font-medium text-blue-200">
                    Sistem Analisis &amp; Pengelolaan Penjualan
                </p>
                <h1 class="mt-5 max-w-2xl text-4xl font-bold tracking-tight sm:text-5xl">
                    Pahami performa penjualan dengan lebih mudah.
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">
                    Kelola data transaksi, pantau tren penjualan, dan eksplorasi hasil analisis dalam satu aplikasi yang sederhana dan terstruktur.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="rounded-xl bg-blue-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-950/30 hover:bg-blue-400">
                        Masuk ke Aplikasi
                    </a>
                    <a href="#fitur" class="rounded-xl border border-slate-600 bg-white/5 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">
                        Lihat Fitur
                    </a>
                </div>
            </div>

            <div class="mx-auto w-full max-w-xl">
                <svg viewBox="0 0 560 390" class="h-auto w-full drop-shadow-2xl" role="img" aria-labelledby="dashboard-preview-title dashboard-preview-description">
                    <title id="dashboard-preview-title">Ilustrasi dashboard analisis penjualan</title>
                    <desc id="dashboard-preview-description">Contoh visual antarmuka dengan kartu ringkasan, grafik tren, dan kategori tanpa data transaksi.</desc>
                    <defs>
                        <linearGradient id="preview-background" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#f8fafc"/>
                            <stop offset="1" stop-color="#eff6ff"/>
                        </linearGradient>
                        <linearGradient id="preview-area" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#3b82f6" stop-opacity=".24"/>
                            <stop offset="1" stop-color="#3b82f6" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <rect x="10" y="10" width="540" height="370" rx="22" fill="#0f172a" fill-opacity=".12"/>
                    <rect x="4" y="4" width="540" height="370" rx="22" fill="url(#preview-background)" stroke="#dbeafe"/>
                    <path d="M4 26a22 22 0 0 1 22-22h496a22 22 0 0 1 22 22v34H4z" fill="#fff"/>
                    <circle cx="30" cy="31" r="5" fill="#f87171"/>
                    <circle cx="48" cy="31" r="5" fill="#fbbf24"/>
                    <circle cx="66" cy="31" r="5" fill="#34d399"/>
                    <rect x="94" y="25" width="116" height="12" rx="6" fill="#e2e8f0"/>
                    <rect x="450" y="22" width="64" height="18" rx="9" fill="#dbeafe"/>
                    <circle cx="463" cy="31" r="4" fill="#2563eb"/>
                    <rect x="472" y="28" width="31" height="6" rx="3" fill="#93c5fd"/>

                    <text x="30" y="91" fill="#0f172a" font-size="18" font-weight="700" font-family="ui-sans-serif, system-ui, sans-serif">Ringkasan Penjualan</text>
                    <text x="30" y="109" fill="#64748b" font-size="10" font-family="ui-sans-serif, system-ui, sans-serif">Visualisasi untuk membantu membaca performa</text>

                    <rect x="30" y="126" width="154" height="60" rx="12" fill="#fff" stroke="#e2e8f0"/>
                    <rect x="45" y="141" width="27" height="27" rx="8" fill="#dbeafe"/>
                    <path d="M52 160v-8m6 8v-13m6 13v-5" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                    <rect x="82" y="142" width="64" height="6" rx="3" fill="#cbd5e1"/>
                    <rect x="82" y="155" width="82" height="10" rx="5" fill="#e2e8f0"/>

                    <rect x="196" y="126" width="154" height="60" rx="12" fill="#fff" stroke="#e2e8f0"/>
                    <rect x="211" y="141" width="27" height="27" rx="8" fill="#dcfce7"/>
                    <path d="m218 157 5-5 5 3 6-7" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="248" y="142" width="64" height="6" rx="3" fill="#cbd5e1"/>
                    <rect x="248" y="155" width="82" height="10" rx="5" fill="#e2e8f0"/>

                    <rect x="362" y="126" width="152" height="60" rx="12" fill="#fff" stroke="#e2e8f0"/>
                    <rect x="377" y="141" width="27" height="27" rx="8" fill="#ede9fe"/>
                    <path d="M391 146v17m-6-6 6 6 6-6" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="414" y="142" width="64" height="6" rx="3" fill="#cbd5e1"/>
                    <rect x="414" y="155" width="82" height="10" rx="5" fill="#e2e8f0"/>

                    <rect x="30" y="202" width="300" height="146" rx="12" fill="#fff" stroke="#e2e8f0"/>
                    <text x="47" y="225" fill="#334155" font-size="11" font-weight="600" font-family="ui-sans-serif, system-ui, sans-serif">Tren Penjualan</text>
                    <rect x="266" y="214" width="46" height="17" rx="8" fill="#f1f5f9"/>
                    <path d="M51 255h257M51 282h257M51 309h257M51 336h257" stroke="#f1f5f9"/>
                    <path d="M52 324 91 308 129 315 168 277 207 286 246 250 286 261 307 240V336H52z" fill="url(#preview-area)"/>
                    <path d="M52 324 91 308 129 315 168 277 207 286 246 250 286 261 307 240" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="168" cy="277" r="4" fill="#fff" stroke="#3b82f6" stroke-width="2"/>
                    <circle cx="246" cy="250" r="4" fill="#fff" stroke="#3b82f6" stroke-width="2"/>

                    <rect x="342" y="202" width="172" height="146" rx="12" fill="#fff" stroke="#e2e8f0"/>
                    <text x="359" y="225" fill="#334155" font-size="11" font-weight="600" font-family="ui-sans-serif, system-ui, sans-serif">Kategori</text>
                    <rect x="359" y="244" width="138" height="7" rx="3.5" fill="#f1f5f9"/>
                    <rect x="359" y="244" width="96" height="7" rx="3.5" fill="#3b82f6"/>
                    <rect x="359" y="266" width="138" height="7" rx="3.5" fill="#f1f5f9"/>
                    <rect x="359" y="266" width="72" height="7" rx="3.5" fill="#8b5cf6"/>
                    <rect x="359" y="288" width="138" height="7" rx="3.5" fill="#f1f5f9"/>
                    <rect x="359" y="288" width="51" height="7" rx="3.5" fill="#10b981"/>
                    <rect x="359" y="310" width="138" height="7" rx="3.5" fill="#f1f5f9"/>
                    <rect x="359" y="310" width="34" height="7" rx="3.5" fill="#f59e0b"/>
                    <rect x="359" y="329" width="68" height="5" rx="2.5" fill="#e2e8f0"/>
                    <rect x="435" y="329" width="45" height="5" rx="2.5" fill="#e2e8f0"/>
                </svg>
            </div>
        </div>
    </section>

    <section id="fitur" class="mt-10">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold text-blue-800">Fitur Utama</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Satu tempat untuk seluruh proses analisis</h2>
        </div>

        <div class="mt-7 grid gap-5 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-800">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M3 3v18h18M7 15l3-3 3 2 5-6"/>
                    </svg>
                </div>
                <h3 class="mt-5 text-lg font-semibold text-slate-900">Dashboard Analisis</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Ringkas tren, kategori, wilayah, dan hasil performa penjualan dalam tampilan visual yang mudah dipahami.</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v4h14v-4"/>
                    </svg>
                </div>
                <h3 class="mt-5 text-lg font-semibold text-slate-900">Pengelolaan Data</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Tambahkan atau perbarui data melalui formulir maupun proses unggah file dengan alur yang terkontrol.</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-800">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 5h16M4 10h16M4 15h10M4 20h10M18 15v5m-2.5-2.5h5"/>
                    </svg>
                </div>
                <h3 class="mt-5 text-lg font-semibold text-slate-900">Penelusuran Transaksi</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Cari dan filter transaksi berdasarkan informasi yang tersedia, lalu buka rinciannya dengan cepat.</p>
            </article>
        </div>
    </section>

    <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="grid gap-8 lg:grid-cols-[0.7fr_1.3fr] lg:items-center">
            <div>
                <p class="text-sm font-semibold text-blue-800">Cara Menggunakan</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Mulai dari yang paling penting</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">Alur singkat untuk membantu pengguna menjalankan analisis secara terstruktur.</p>
            </div>
            <ol class="grid gap-4 sm:grid-cols-3">
                <li class="rounded-xl bg-slate-50 p-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-sm font-bold text-white">1</span>
                    <h3 class="mt-3 font-semibold text-slate-900">Masuk</h3>
                    <p class="mt-1 text-sm text-slate-600">Gunakan akun yang telah diberikan administrator.</p>
                </li>
                <li class="rounded-xl bg-slate-50 p-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-sm font-bold text-white">2</span>
                    <h3 class="mt-3 font-semibold text-slate-900">Kelola Data</h3>
                    <p class="mt-1 text-sm text-slate-600">Pastikan data transaksi tersedia dan telah diperbarui.</p>
                </li>
                <li class="rounded-xl bg-slate-50 p-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-sm font-bold text-white">3</span>
                    <h3 class="mt-3 font-semibold text-slate-900">Analisis</h3>
                    <p class="mt-1 text-sm text-slate-600">Gunakan filter dan dashboard untuk membaca hasil.</p>
                </li>
            </ol>
        </div>
    </section>

    <section class="mt-10 flex flex-col items-center justify-between gap-5 rounded-2xl border border-blue-100 bg-blue-50 px-6 py-7 sm:flex-row sm:px-8">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Siap mulai bekerja?</h2>
            <p class="mt-1 text-sm text-slate-600">Masuk menggunakan akun yang telah didaftarkan.</p>
        </div>
        <a href="{{ route('login') }}" class="shrink-0 rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">
            Masuk Sekarang
        </a>
    </section>
@endsection
