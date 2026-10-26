<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f8f2">
    <title>Kelola Produk | Green Plant</title>
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
                    Katalog
                </a>
                <a href="{{ route('admin.produk.index') }}" aria-current="page" class="flex shrink-0 items-center gap-3 rounded-lg bg-white/15 px-3 py-2.5 text-sm font-medium">
                    <span aria-hidden="true">▤</span>
                    Kelola Produk
                </a>
                <a href="{{ route('admin.produk.create') }}" class="flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/80 transition hover:bg-white/10 hover:text-white">
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
            <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Green Plant / Admin</p>
                    <h1 class="mt-1 text-xl font-bold text-gray-900 sm:text-2xl">Kelola Produk</h1>
                </div>
                <a href="{{ route('admin.produk.create') }}" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
                    + Tambah Produk
                </a>
            </header>

            <div class="space-y-5 p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <p role="status" class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</p>
                @endif

                <section aria-label="Daftar produk" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    @if ($produk->isEmpty())
                        <div class="px-6 py-14 text-center">
                            <p class="font-semibold text-gray-800">Belum ada produk.</p>
                            <a href="{{ route('admin.produk.create') }}" class="mt-3 inline-flex text-sm font-semibold text-brand-800 hover:text-brand-900">Tambah produk pertama</a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[48rem] text-left text-sm">
                                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th scope="col" class="px-5 py-3 font-medium">Produk</th>
                                        <th scope="col" class="px-3 py-3 font-medium">Kategori</th>
                                        <th scope="col" class="px-3 py-3 text-right font-medium">Harga</th>
                                        <th scope="col" class="px-3 py-3 text-right font-medium">Stok</th>
                                        <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($produk as $item)
                                        <tr>
                                            <td class="px-5 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-gray-50">
                                                        <x-gambar-produk :foto="$item->foto" :nama="$item->nama" />
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-gray-900">{{ $item->nama }}</p>
                                                        <p class="mt-0.5 max-w-xs truncate text-xs text-gray-500">{{ $item->deskripsi }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 text-gray-600">{{ $item->kategori?->nama ?? 'Tanpa kategori' }}</td>
                                            <td class="px-3 py-3 text-right text-gray-700">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                                            <td class="px-3 py-3 text-right text-gray-700">{{ number_format($item->stok) }}</td>
                                            <td class="px-5 py-3">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('admin.produk.edit', $item) }}" class="font-medium text-brand-800 hover:text-brand-900">Edit</a>
                                                    <form method="POST" action="{{ route('admin.produk.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus produk {{ $item->nama }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="font-medium text-red-700 hover:text-red-800">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-gray-100 px-5 py-4">
                            {{ $produk->links() }}
                        </div>
                    @endif
                </section>
            </div>
        </main>
    </div>
</body>
</html>
