<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f8f2">
    <title>{{ $produk ? 'Edit Produk' : 'Input Produk' }} | Green Plant</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="flex w-full flex-col bg-brand-900 text-white lg:min-h-screen lg:w-60 lg:shrink-0">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
                <x-logo-icon class="h-9 w-9 text-brand-100" />
                <span class="text-sm font-semibold leading-tight">Green Plant<br><span class="font-normal text-brand-100/70">Admin</span></span>
            </a>

            <nav aria-label="Navigasi admin" class="flex gap-2 overflow-x-auto px-3 py-4 lg:flex-1 lg:flex-col">
                <a href="{{ route('admin.dashboard') }}" class="flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/80 transition hover:bg-white/10 hover:text-white">
                    <span aria-hidden="true">▦</span>
                    Dashboard
                </a>
                <a href="{{ route('produk.index') }}" class="flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/80 transition hover:bg-white/10 hover:text-white">
                    <span aria-hidden="true">♧</span>
                    Produk
                </a>
                <a href="{{ route('admin.produk.index') }}" class="flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/80 transition hover:bg-white/10 hover:text-white">
                    <span aria-hidden="true">▤</span>
                    Kelola Produk
                </a>
                <a href="{{ route('admin.produk.create') }}" @if (! $produk) aria-current="page" @endif class="flex shrink-0 items-center gap-3 rounded-lg {{ $produk ? 'px-3 py-2.5 text-sm text-white/80 transition hover:bg-white/10 hover:text-white' : 'bg-white/15 px-3 py-2.5 text-sm font-medium' }}">
                    <span aria-hidden="true">＋</span>
                    Input Produk
                </a>
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10 p-3">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-white/80 transition hover:bg-white/10 hover:text-white">
                    <span aria-hidden="true">↪</span>
                    Keluar
                </button>
            </form>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="border-b border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Green Plant / Admin / Produk</p>
                <h1 class="mt-1 text-xl font-bold text-gray-900 sm:text-2xl">{{ $produk ? 'Edit Produk' : 'Input Produk' }}</h1>
            </header>

            <div class="mx-auto max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <p role="status" class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ session('status') }}</p>
                @endif

                @include('admin.produk._form')
            </div>
        </main>
    </div>
</body>
</html>
