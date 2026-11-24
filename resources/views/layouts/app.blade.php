<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Analisis Penjualan')</title>
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
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold text-slate-900">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-900 text-white" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19V9m5 10V5m5 14v-7m5 7V3M2 19h20"/>
                    </svg>
                </span>
                Analisis Penjualan
            </a>
            @auth
                <div class="ml-auto flex items-center gap-3">
                    <span class="hidden text-sm text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
                    <x-theme-toggle />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Keluar</button>
                    </form>
                </div>
            @else
                <div class="ml-auto flex items-center gap-3">
                    <x-theme-toggle />
                    <a href="{{ route('login') }}" class="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        Masuk
                    </a>
                </div>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
