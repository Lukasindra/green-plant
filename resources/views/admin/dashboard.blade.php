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
    @php
        // [nama, nama route (null = halaman belum dibuat), ikon, aktif]
        $menu = [
            ['Dashboard', 'admin.dashboard', '▦', true],
            ['Produk', 'produk.index', '♧', false],
            ['Jasa', null, '⚒', false],
            ['Pesanan', null, '▤', false],
            ['Pembayaran', null, 'Rp', false],
            ['Pengiriman', null, '⇢', false],
            ['Laporan', null, '▥', false],
            ['Pengguna', null, '☺', false],
            ['Pengaturan', null, '⚙', false],
        ];

        // Angka ringkas untuk label sumbu grafik: 2,5 jt / 750 rb
        $ringkas = function (int $angka): string {
            if ($angka >= 1000000) {
                return 'Rp ' . rtrim(rtrim(number_format($angka / 1000000, 1, ',', '.'), '0'), ',') . ' jt';
            }
            if ($angka >= 1000) {
                return 'Rp ' . number_format($angka / 1000, 0, ',', '.') . ' rb';
            }

            return 'Rp ' . $angka;
        };
    @endphp

    <div class="min-h-screen lg:flex">
        <aside class="flex w-full flex-col bg-brand-900 text-white lg:min-h-screen lg:w-60 lg:shrink-0">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
                <x-logo-icon class="h-9 w-9 text-brand-100" />
                <span class="text-sm font-semibold leading-tight">Green Plant<br><span class="font-normal text-brand-100/70">Admin</span></span>
            </a>

            <nav aria-label="Navigasi admin" class="flex gap-2 overflow-x-auto px-3 py-4 lg:flex-1 lg:flex-col">
                @foreach ($menu as [$nama, $rute, $ikon, $aktif])
                    @if ($rute)
                        <a href="{{ route($rute) }}" @if ($aktif) aria-current="page" @endif
                           class="flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ $aktif ? 'bg-white/15 font-medium' : 'text-white/80 transition hover:bg-white/10 hover:text-white' }}">
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
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Dashboard</h1>
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

                <section aria-label="Ringkasan data" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">Total Pesanan</p>
                                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($jumlahPesanan) }}</p>
                                <p class="mt-1 text-xs text-gray-500">Pesanan</p>
                            </div>
                            <span class="rounded-lg bg-emerald-50 p-3 text-xl text-brand-700" aria-hidden="true">▤</span>
                        </div>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">Total Produk</p>
                                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($jumlahProduk) }}</p>
                                <p class="mt-1 text-xs text-gray-500">Produk</p>
                            </div>
                            <span class="rounded-lg bg-lime-50 p-3 text-xl text-lime-700" aria-hidden="true">♧</span>
                        </div>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">Total Jasa</p>
                                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($jumlahJasa) }}</p>
                                <p class="mt-1 text-xs text-gray-500">Jasa</p>
                            </div>
                            <span class="rounded-lg bg-teal-50 p-3 text-xl text-teal-700" aria-hidden="true">⚒</span>
                        </div>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">Total Pendapatan</p>
                                <p class="mt-3 text-2xl font-bold text-brand-800">Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs text-gray-500">Bulan ini (pesanan lunas)</p>
                            </div>
                            <span class="rounded-lg bg-green-50 p-3 text-xl text-green-700" aria-hidden="true">Rp</span>
                        </div>
                    </article>
                </section>

                <section class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(20rem,0.9fr)]">
                    <article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h2 class="font-semibold text-gray-900">Pesanan Terbaru</h2>
                            <p class="mt-1 text-xs text-gray-500">Pesanan produk dan jasa yang baru dibuat</p>
                        </div>

                        @if ($recentOrders->isEmpty())
                            <p class="px-5 py-10 text-center text-sm text-gray-500">Belum ada pesanan.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[40rem] text-left text-sm">
                                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th scope="col" class="px-5 py-3 font-medium">Kode / Pelanggan</th>
                                            <th scope="col" class="px-3 py-3 font-medium">Jenis</th>
                                            <th scope="col" class="px-3 py-3 font-medium">Status</th>
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
                                                <td class="px-3 py-3.5">
                                                    @if ($order['status'])
                                                        <x-status-badge :nilai="$order['status']" />
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
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
                                <h2 class="font-semibold text-gray-900">Penjualan</h2>
                                <p class="mt-1 text-xs text-gray-500">30 hari terakhir</p>
                            </div>
                            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800">30 Hari</span>
                        </div>

                        <div class="mt-5">
                            <svg viewBox="0 0 600 210" role="img" aria-label="Grafik penjualan harian selama 30 hari terakhir" class="h-52 w-full overflow-visible">
                                <line x1="0" y1="20" x2="600" y2="20" stroke="#e5e7eb" stroke-dasharray="4 5" />
                                <line x1="0" y1="100" x2="600" y2="100" stroke="#e5e7eb" stroke-dasharray="4 5" />
                                <line x1="0" y1="180" x2="600" y2="180" stroke="#e5e7eb" />
                                <polygon points="0,180 {{ $chartPoints }} 600,180" fill="#e0efe1" opacity="0.65" />
                                <polyline points="{{ $chartPoints }}" fill="none" stroke="#1f6f31" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" />
                                <text x="2" y="14" font-size="11" fill="#9ca3af">{{ $ringkas($puncakNilai) }}</text>
                                <text x="2" y="94" font-size="11" fill="#9ca3af">{{ $ringkas(intdiv($puncakNilai, 2)) }}</text>
                                <text x="2" y="174" font-size="11" fill="#9ca3af">Rp 0</text>
                            </svg>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>{{ $salesChart->first()['label'] }}</span>
                                <span>{{ $salesChart->slice(14, 1)->first()['label'] }}</span>
                                <span>{{ $salesChart->last()['label'] }}</span>
                            </div>
                            <p class="mt-4 text-xs leading-5 text-gray-500">Grafik hanya menghitung pesanan yang sudah lunas.</p>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
