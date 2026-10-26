<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f8f2">
    <title>Dashboard Admin | Green Plant</title>
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
                <a href="{{ route('admin.dashboard') }}" aria-current="page" class="flex shrink-0 items-center gap-3 rounded-lg bg-white/15 px-3 py-2.5 text-sm font-medium">
                    <span aria-hidden="true">▦</span>
                    Dashboard
                </a>
                <a href="{{ route('admin.produk.index') }}" class="flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/80 transition hover:bg-white/10 hover:text-white">
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
            <header class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Green Plant / Admin</p>
                    <h1 class="mt-1 text-xl font-bold text-gray-900 sm:text-2xl">Dashboard</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden text-right sm:block">
                        <span class="block text-sm font-semibold text-gray-900">{{ auth()->user()->nama }}</span>
                        <span class="block text-xs text-gray-500">Administrator</span>
                    </span>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-800" aria-hidden="true">
                        {{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}
                    </span>
                </div>
            </header>

            <div class="space-y-6 p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <p role="status" class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ session('status') }}</p>
                @endif

                <section aria-label="Ringkasan data" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">Total Pesanan</p>
                                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($jumlahPesanan) }}</p>
                                <p class="mt-1 text-xs text-gray-500">Produk dan jasa</p>
                            </div>
                            <span class="rounded-lg bg-emerald-50 p-3 text-xl text-brand-700" aria-hidden="true">▤</span>
                        </div>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">Total Produk</p>
                                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($jumlahProduk) }}</p>
                                <p class="mt-1 text-xs text-gray-500">Produk terdaftar</p>
                            </div>
                            <span class="rounded-lg bg-lime-50 p-3 text-xl text-lime-700" aria-hidden="true">♧</span>
                        </div>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">Total Jasa</p>
                                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($jumlahJasa) }}</p>
                                <p class="mt-1 text-xs text-gray-500">Jasa terdaftar</p>
                            </div>
                            <span class="rounded-lg bg-teal-50 p-3 text-xl text-teal-700" aria-hidden="true">⚒</span>
                        </div>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">Nilai Total Pesanan</p>
                                <p class="mt-3 text-2xl font-bold text-brand-800">Rp {{ number_format($nilaiPesanan, 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs text-gray-500">Bukan total pembayaran terverifikasi</p>
                            </div>
                            <span class="rounded-lg bg-green-50 p-3 text-xl text-green-700" aria-hidden="true">Rp</span>
                        </div>
                    </article>
                </section>

                <section class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(20rem,0.9fr)]">
                    <article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4">
                            <div>
                                <h2 class="font-semibold text-gray-900">Pesanan Terbaru</h2>
                                <p class="mt-1 text-xs text-gray-500">Pesanan produk dan jasa yang baru dibuat</p>
                            </div>
                        </div>

                        @if ($recentOrders->isEmpty())
                            <p class="px-5 py-10 text-center text-sm text-gray-500">Belum ada pesanan.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[36rem] text-left text-sm">
                                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th scope="col" class="px-5 py-3 font-medium">Kode / Pelanggan</th>
                                            <th scope="col" class="px-3 py-3 font-medium">Jenis</th>
                                            <th scope="col" class="px-3 py-3 font-medium">Tanggal</th>
                                            <th scope="col" class="px-5 py-3 text-right font-medium">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($recentOrders as $order)
                                            <tr>
                                                <td class="px-5 py-3.5">
                                                    <span class="block font-medium text-gray-900">{{ $order['kode'] }}</span>
                                                    <span class="mt-1 block text-xs text-gray-500">{{ $order['pelanggan'] }}</span>
                                                </td>
                                                <td class="px-3 py-3.5 text-gray-600">{{ $order['tipe'] }}</td>
                                                <td class="whitespace-nowrap px-3 py-3.5 text-gray-600">{{ $order['tanggal']->format('d/m/Y') }}</td>
                                                <td class="whitespace-nowrap px-5 py-3.5 text-right font-medium text-gray-800">Rp {{ number_format($order['nilai'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-gray-900">Nilai Pesanan</h2>
                                <p class="mt-1 text-xs text-gray-500">30 hari terakhir, berdasarkan tanggal pemesanan</p>
                            </div>
                            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800">30 hari</span>
                        </div>

                        <div class="mt-5">
                            <svg viewBox="0 0 600 210" role="img" aria-label="Grafik nilai pesanan harian selama 30 hari terakhir" class="h-52 w-full overflow-visible">
                                <line x1="0" y1="20" x2="600" y2="20" stroke="#e5e7eb" stroke-dasharray="4 5" />
                                <line x1="0" y1="100" x2="600" y2="100" stroke="#e5e7eb" stroke-dasharray="4 5" />
                                <line x1="0" y1="180" x2="600" y2="180" stroke="#e5e7eb" />
                                <polygon points="0,180 {{ $chartPoints }} 600,180" fill="#e0efe1" opacity="0.65" />
                                <polyline points="{{ $chartPoints }}" fill="none" stroke="#1f6f31" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" />
                            </svg>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>{{ $salesChart->first()['label'] }}</span>
                                <span>{{ $salesChart->slice(14, 1)->first()['label'] }}</span>
                                <span>{{ $salesChart->last()['label'] }}</span>
                            </div>
                            <p class="mt-4 text-xs leading-5 text-gray-500">Grafik menunjukkan nilai pesanan, bukan pembayaran yang telah dikonfirmasi.</p>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
