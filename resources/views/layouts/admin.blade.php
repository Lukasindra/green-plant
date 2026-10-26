<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f8f2">
    <title>@hasSection('judul')@yield('judul')@else Dashboard @endif | Admin | Green Plant</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 antialiased">
    @php
        // [nama, nama route (null = halaman belum dibuat), ikon, pola route aktif]
        $adminMenu = [
            ['Dashboard', 'admin.dashboard', '▦', 'admin.dashboard'],
            ['Produk', 'admin.produk.index', '♧', 'admin.produk.*'],
            ['Jasa', 'admin.jasa.index', '⚒', 'admin.jasa.*'],
            ['Pesanan', 'admin.pesanan.index', '▤', 'admin.pesanan.*'],
            ['Pembayaran', 'admin.pembayaran.index', 'Rp', 'admin.pembayaran.*'],
            ['Pengiriman', 'admin.pengiriman.index', '⇢', 'admin.pengiriman.*'],
            ['Laporan', 'admin.laporan.index', '▥', 'admin.laporan.*'],
            ['Pengguna', 'admin.pengguna.index', '☺', 'admin.pengguna.*'],
            ['Pengaturan', 'admin.pengaturan.index', '⚙', 'admin.pengaturan.*'],
        ];
    @endphp

    <div class="min-h-screen lg:flex">
        <aside class="flex w-full flex-col bg-brand-900 text-white lg:min-h-screen lg:w-60 lg:shrink-0">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
                <x-logo-icon class="h-9 w-9 text-brand-100" />
                <span class="text-sm font-semibold leading-tight">Green Plant<br><span class="font-normal text-brand-100/70">Admin</span></span>
            </a>

            <nav aria-label="Navigasi admin" class="flex gap-2 overflow-x-auto px-3 py-4 lg:flex-1 lg:flex-col">
                @foreach ($adminMenu as [$nama, $rute, $ikon, $polaAktif])
                    @if ($rute)
                        <a href="{{ route($rute) }}" @if (request()->routeIs($polaAktif)) aria-current="page" @endif
                           class="flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs($polaAktif) ? 'bg-white/15 font-medium' : 'text-white/80 transition hover:bg-white/10 hover:text-white' }}">
                            <span aria-hidden="true" class="w-5 text-center">{{ $ikon }}</span>
                            {{ $nama }}
                        </a>
                    @else
                        <span aria-disabled="true" title="Segera hadir"
                              class="flex shrink-0 cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/40">
                            <span aria-hidden="true" class="w-5 text-center">{{ $ikon }}</span>
                            {{ $nama }}
                        </span>
                    @endif
                @endforeach
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10 p-3">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-white/80 transition hover:bg-white/10 hover:text-white">
                    <span aria-hidden="true" class="w-5 text-center">↪</span>
                    Logout
                </button>
            </form>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">@yield('judul', 'Dashboard')</h1>
                <div class="flex items-center gap-4">
                    <span title="Notifikasi belum tersedia" aria-label="Notifikasi" class="text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10.3 20a1.9 1.9 0 0 0 3.4 0"/></svg>
                    </span>
                    <span class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-800" aria-hidden="true">
                            {{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}
                        </span>
                        <span class="hidden text-left sm:block">
                            <span class="block text-sm font-semibold text-gray-900">{{ auth()->user()->nama }}</span>
                            <span class="block text-xs text-gray-500">Administrator</span>
                        </span>
                    </span>
                </div>
            </header>

            <div class="space-y-6 p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <p role="status" class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</p>
                @endif

                @yield('isi')
            </div>
        </main>
    </div>
</body>
</html>
