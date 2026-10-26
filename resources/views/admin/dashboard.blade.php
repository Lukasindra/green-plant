@extends('layouts.admin')

@section('judul', 'Dashboard')

@section('isi')
    @php
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
                    <p class="mt-1 text-xs text-gray-500">Produk</p>
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
@endsection
