<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Beranda') | Green Plant</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #menu-mobile { transform: translateX(100%); transition: transform .2s ease; }
        #menu-backdrop { opacity: 0; transition: opacity .2s ease; }
        html.menu-open #menu-mobile { transform: none; }
        html.menu-open #menu-backdrop { opacity: 1; pointer-events: auto; }
        html.menu-open { overflow: hidden; }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-gray-50 font-sans text-gray-800 antialiased">
<a href="#isi-utama"
    class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
    Langsung ke konten
</a>

    @php
        $jumlahKeranjang = array_sum(session('cart', []));
        $cariJasa = request()->routeIs('jasa.*');
        $menu = [
            ['Beranda', url('/'), request()->is('/')],
            ['Produk', route('produk.index'), request()->routeIs('produk.*')],
            ['Jasa', route('jasa.index'), request()->routeIs('jasa.*')],
            ['Tentang Kami', route('tentang'), request()->routeIs('tentang')],
        ];
    @endphp

    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ url('/') }}"><x-logo /></a>

            <nav class="hidden items-center gap-8 text-sm md:flex">
                @foreach ($menu as [$nama, $link, $aktif])
                    <a href="{{ $link }}"
                       class="border-b-2 py-1 {{ $aktif ? 'border-brand-700 font-semibold text-brand-700' : 'border-transparent text-gray-600 hover:text-brand-700' }}">
                        {{ $nama }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-4 text-gray-700">
                <button id="menu-toggle" type="button" aria-controls="menu-mobile" aria-expanded="false" aria-label="Buka menu"
                    class="rounded-lg p-2 transition hover:bg-gray-100 hover:text-brand-700 md:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                </button>
                <form method="GET" action="{{ $cariJasa ? route('jasa.index') : route('produk.index') }}" class="hidden md:flex">
                    <label for="q" class="sr-only">Cari</label>
                    <input id="q" type="search" name="q" value="{{ request('q') }}"
                        placeholder="{{ $cariJasa ? 'Cari jasa…' : 'Cari produk…' }}"
                        class="w-40 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    @if (filled(request('kategori')))
                        <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                    @endif
                </form>
                <a href="{{ route('keranjang.index') }}" aria-label="Keranjang" class="relative hover:text-brand-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h8.8a1 1 0 0 0 1-.8L20 8H6"/><circle cx="9" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
                    @if ($jumlahKeranjang > 0)
                        <span class="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-700 px-1 text-[10px] font-semibold text-white">{{ $jumlahKeranjang }}</span>
                    @endif
                </a>

                @auth
                    <div class="flex items-center gap-3 text-sm">
                        @if (auth()->user()->isCustomer())
                            <a href="{{ route('pesanan.index') }}" class="text-xs font-medium text-gray-600 hover:text-brand-700">Pesanan</a>
                        @endif
                        @if (auth()->user()->isKurir())
                            <a href="{{ route('kurir.index') }}" class="text-xs font-medium text-gray-600 hover:text-brand-700">Tugas Antar</a>
                        @endif
                        <span class="hidden max-w-32 truncate text-gray-700 md:inline">{{ auth()->user()->nama }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-brand-700 hover:underline">Keluar</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" aria-label="Login" class="hover:text-brand-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <div id="menu-backdrop" class="pointer-events-none fixed inset-0 z-30 bg-black/50 md:hidden" aria-hidden="true"></div>

    <div id="menu-mobile" class="fixed inset-y-0 right-0 z-40 w-64 overflow-y-auto bg-white shadow-xl md:hidden" aria-label="Menu utama">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <x-logo />
            <button id="menu-close" type="button" aria-label="Tutup menu"
                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
            </button>
        </div>

        <nav class="flex flex-col gap-1 px-3 py-4">
            <form method="GET" action="{{ $cariJasa ? route('jasa.index') : route('produk.index') }}" class="mb-2">
                <label for="q-mobile" class="sr-only">Cari</label>
                <input id="q-mobile" type="search" name="q" value="{{ request('q') }}"
                    placeholder="{{ $cariJasa ? 'Cari jasa…' : 'Cari produk…' }}"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                @if (filled(request('kategori')))
                    <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                @endif
            </form>
            @foreach ($menu as [$nama, $link, $aktif])
                <a href="{{ $link }}" @if ($aktif) aria-current="page" @endif
                   class="rounded-lg px-3 py-2.5 text-sm {{ $aktif ? 'bg-brand-50 font-semibold text-brand-700' : 'text-gray-600 transition hover:bg-gray-50 hover:text-brand-700' }}">
                    {{ $nama }}
                </a>
            @endforeach

            <div class="mt-2 border-t border-gray-100 pt-3">
                @auth
                    @if (auth()->user()->isCustomer())
                        <a href="{{ route('pesanan.index') }}" class="block rounded-lg px-3 py-2.5 text-sm text-gray-600 transition hover:bg-gray-50 hover:text-brand-700">Pesanan Saya</a>
                    @endif
                    @if (auth()->user()->isKurir())
                        <a href="{{ route('kurir.index') }}" class="block rounded-lg px-3 py-2.5 text-sm text-gray-600 transition hover:bg-gray-50 hover:text-brand-700">Tugas Antar</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-lg px-3 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2.5 text-sm text-gray-600 transition hover:bg-gray-50 hover:text-brand-700">Login</a>
                    <a href="{{ route('daftar') }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">Daftar</a>
                @endauth
            </div>
        </nav>
    </div>

    <main id="isi-utama" tabindex="-1" class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 outline-none">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        @yield('isi')
    </main>

    <footer class="mt-8 bg-brand-900 text-brand-100">
        <div class="mx-auto max-w-6xl px-4 py-8">
            <div class="grid gap-8 sm:grid-cols-3">
                <div>
                    <x-logo :terang="true" />
                    <p class="mt-2 text-sm text-brand-200">Solusi hijau untuk taman impian Anda.</p>
                </div>

                <nav aria-label="Tautan cepat">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-200">Jelajahi</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('produk.index') }}" class="text-brand-100/90 hover:text-white hover:underline">Produk</a></li>
                        <li><a href="{{ route('jasa.index') }}" class="text-brand-100/90 hover:text-white hover:underline">Jasa</a></li>
                        <li><a href="{{ route('tentang') }}" class="text-brand-100/90 hover:text-white hover:underline">Tentang Kami</a></li>
                    </ul>
                </nav>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-200">Metode Pembayaran</p>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach (config('toko.metode') as $namaMetode)
                            <li class="rounded-full border border-white/20 px-3 py-1 text-xs text-brand-100/90">{{ $namaMetode }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="mt-8 border-t border-white/10 pt-4 text-center text-xs text-brand-200/80">
                &copy; {{ date('Y') }} Green Plant
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var html = document.documentElement;
            var tombolBuka = document.getElementById('menu-toggle');
            if (!tombolBuka) return;

            function aturStatus(terbuka) {
                html.classList.toggle('menu-open', terbuka);
                tombolBuka.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
            }

            tombolBuka.addEventListener('click', function () {
                aturStatus(!html.classList.contains('menu-open'));
            });
            document.getElementById('menu-close').addEventListener('click', function () { aturStatus(false); });
            document.getElementById('menu-backdrop').addEventListener('click', function () { aturStatus(false); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') aturStatus(false);
            });
            document.querySelectorAll('#menu-mobile nav a').forEach(function (a) {
                a.addEventListener('click', function () { aturStatus(false); });
            });
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
