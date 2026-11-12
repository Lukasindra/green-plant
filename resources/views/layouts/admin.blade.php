<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f8f2">
    <title>@hasSection('judul')@yield('judul')@else Dashboard @endif | Admin | Green Plant</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #sb { transform: translateX(-100%); transition: transform .2s ease; }
        #sb-main { transition: padding .2s ease; }
        #sb-backdrop { opacity: 0; transition: opacity .2s ease; }
        html.sb-mobile-open #sb { transform: none; }
        html.sb-mobile-open #sb-backdrop { opacity: 1; pointer-events: auto; }
        html.sb-mobile-open { overflow: hidden; }
        @media (min-width: 1024px) {
            #sb, html.sb-mobile-open #sb { transform: none; }
            #sb-main { padding-left: 15rem; }
            html.sb-desktop-closed #sb { transform: translateX(-100%); }
            html.sb-desktop-closed #sb-main { padding-left: 0; }
            #sb-backdrop { display: none; }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 antialiased">
<a href="#sb-main"
    class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
    Langsung ke konten
</a>
<script>
    try { if (localStorage.getItem('gp-admin-sidebar') === 'closed') document.documentElement.classList.add('sb-desktop-closed') } catch (e) {}
</script>
    {{-- Backdrawer: lapisan gelap di belakang sidebar saat dibuka di layar kecil --}}
    <div id="sb-backdrop" class="pointer-events-none fixed inset-0 z-30 bg-black/50 lg:hidden" aria-hidden="true"></div>

    @php
        // [nama, nama route (null = halaman belum dibuat), ikon, pola route aktif]
        $labelRole = match (auth()->user()->role) {
            'admin' => 'Administrator',
            'kurir' => 'Kurir',
            default => 'Pelanggan',
        };
        $adminMenu = [
            ['Dashboard', 'admin.dashboard', 'dashboard', 'admin.dashboard'],
            ['Produk', 'admin.produk.index', 'produk', 'admin.produk.*'],
            ['Jasa', 'admin.jasa.index', 'jasa', 'admin.jasa.*'],
            ['Pesanan', 'admin.pesanan.index', 'pesanan', 'admin.pesanan.*'],
            ['Pembayaran', 'admin.pembayaran.index', 'pembayaran', 'admin.pembayaran.*'],
            ['Pengiriman', 'admin.pengiriman.index', 'pengiriman', 'admin.pengiriman.*'],
            ['Laporan', 'admin.laporan.index', 'laporan', 'admin.laporan.*'],
            ['Pengguna', 'admin.pengguna.index', 'pengguna', 'admin.pengguna.*'],
            ['Pengaturan', 'admin.pengaturan.index', 'pengaturan', 'admin.pengaturan.*'],
        ];
    @endphp

    <aside id="sb" class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col overflow-y-auto bg-brand-900 text-white" aria-label="Panel admin">
        <div class="flex items-center justify-between border-b border-white/10 pl-5 pr-3 py-5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <x-logo-icon class="h-9 w-9 text-brand-100" />
                <span class="text-sm font-semibold leading-tight">Green Plant<br><span class="font-normal text-brand-100/70">Admin</span></span>
            </a>
            <button id="sb-close" type="button" aria-label="Tutup menu"
                class="rounded-lg p-2 text-white/80 transition hover:bg-white/10 hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
            </button>
        </div>

        <nav aria-label="Navigasi admin" class="flex flex-1 flex-col gap-1 px-3 py-4">
            @foreach ($adminMenu as [$nama, $rute, $ikon, $polaAktif])
                @if ($rute)
                    <a href="{{ route($rute) }}" @if (request()->routeIs($polaAktif)) aria-current="page" @endif
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs($polaAktif) ? 'bg-white/15 font-medium' : 'text-white/80 transition hover:bg-white/10 hover:text-white' }}">
                        <x-sidebar-ikon :nama="$ikon" />
                        {{ $nama }}
                    </a>
                @else
                    <span aria-disabled="true" title="Segera hadir"
                          class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/40">
                        <x-sidebar-ikon :nama="$ikon" />
                        {{ $nama }}
                    </span>
                @endif
            @endforeach
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10 p-3">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-white/80 transition hover:bg-white/10 hover:text-white">
                <x-sidebar-ikon nama="keluar" />
                Logout
            </button>
        </form>
    </aside>

    <div id="sb-main" tabindex="-1" class="flex min-h-screen flex-col outline-none">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
            <button id="sb-toggle" type="button" aria-controls="sb" aria-expanded="true" aria-label="Buka atau tutup menu"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
            </button>
            <h1 class="min-w-0 truncate text-xl font-bold text-gray-900 sm:text-2xl">@yield('judul', 'Dashboard')</h1>
            <div class="ml-auto flex items-center gap-4">
                <span title="Notifikasi belum tersedia" aria-label="Notifikasi" class="text-gray-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10.3 20a1.9 1.9 0 0 0 3.4 0"/></svg>
                </span>
                <span class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-800" aria-hidden="true">
                        {{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-semibold text-gray-900">{{ auth()->user()->nama }}</span>
                        <span class="block text-xs text-gray-500">{{ $labelRole }}</span>
                    </span>
                </span>
            </div>
        </header>

        <main class="min-w-0 flex-1">
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

    <script>
        (function () {
            var html = document.documentElement;
            var key = 'gp-admin-sidebar';
            var desktop = window.matchMedia('(min-width: 1024px)');

            function refreshAria() {
                var open = desktop.matches
                    ? !html.classList.contains('sb-desktop-closed')
                    : html.classList.contains('sb-mobile-open');
                btn.setAttribute('aria-expanded', String(open));
            }

            function toggle() {
                if (desktop.matches) {
                    var closed = html.classList.toggle('sb-desktop-closed');
                    try { localStorage.setItem(key, closed ? 'closed' : 'open') } catch (e) {}
                } else {
                    html.classList.toggle('sb-mobile-open');
                }
                refreshAria();
            }

            function tutupMobile() {
                if (!html.classList.contains('sb-mobile-open')) return;
                html.classList.remove('sb-mobile-open');
                refreshAria();
            }

            var btn = document.getElementById('sb-toggle');
            btn.addEventListener('click', toggle);
            document.getElementById('sb-close').addEventListener('click', function () {
                if (desktop.matches) toggle(); else tutupMobile();
            });
            document.getElementById('sb-backdrop').addEventListener('click', tutupMobile);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') tutupMobile();
            });
            document.querySelectorAll('#sb nav a').forEach(function (a) {
                a.addEventListener('click', function () { if (!desktop.matches) tutupMobile(); });
            });
            desktop.addEventListener('change', function (e) {
                html.classList.remove('sb-mobile-open', 'sb-desktop-closed');
                if (e.matches) {
                    try { if (localStorage.getItem(key) === 'closed') html.classList.add('sb-desktop-closed') } catch (err) {}
                }
                refreshAria();
            });
            refreshAria();
        })();

        // Anti double-submit: kunci tombol submit form POST selama diproses.
        // Form yang dibatalkan lewat confirm() (defaultPrevented) tidak ikut dikunci.
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if ((form.getAttribute('method') || '').toLowerCase() !== 'post') return;
            if (e.defaultPrevented) return;
            var tombol = form.querySelector('button:not([type]), button[type="submit"]');
            if (!tombol || tombol.disabled) return;
            tombol.disabled = true;
            if (!tombol.dataset.labelAsli) tombol.dataset.labelAsli = tombol.textContent;
            tombol.textContent = 'Memproses…';
        });
    </script>
</body>
</html>
