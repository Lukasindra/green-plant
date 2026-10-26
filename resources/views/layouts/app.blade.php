<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Beranda') | Green Plant</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-gray-50 font-sans text-gray-800 antialiased">

    @php
        $jumlahKeranjang = array_sum(session('cart', []));
        $menu = [
            ['Beranda', url('/'), request()->is('/')],
            ['Produk', route('produk.index'), request()->routeIs('produk.*')],
            ['Jasa', '#', false],
            ['Tentang Kami', '#', false],
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
                <a href="#" aria-label="Cari" class="hover:text-brand-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </a>
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

        <nav class="flex gap-6 overflow-x-auto border-t border-gray-100 px-4 py-2 text-sm md:hidden">
            @foreach ($menu as [$nama, $link, $aktif])
                <a href="{{ $link }}" class="whitespace-nowrap {{ $aktif ? 'font-semibold text-brand-700' : 'text-gray-600' }}">{{ $nama }}</a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        @yield('isi')
    </main>

    <footer class="mt-8 bg-brand-900 text-brand-100">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-6 text-sm md:flex-row">
            <x-logo :terang="true" />
            <p class="text-brand-200">Solusi hijau untuk taman impian Anda.</p>
            <p class="text-xs text-brand-200/80">&copy; {{ date('Y') }} Green Plant</p>
        </div>
    </footer>
</body>
</html>
