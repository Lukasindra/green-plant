@extends('layouts.admin')

@section('judul', 'Pesanan')

@section('isi')
    @php
        $daftarStatus = [
            '' => 'Semua status',
            'menunggu_bayar' => 'Menunggu bayar',
            'menunggu_verifikasi' => 'Menunggu verifikasi',
            'lunas' => 'Lunas',
            'dikirim' => 'Dikirim',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];

        $daftarTipe = [
            'semua' => 'Semua',
            'produk' => 'Produk',
            'jasa' => 'Jasa',
        ];
    @endphp

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Filter jenis pesanan">
            @foreach ($daftarTipe as $tipeOpsi => $labelTipe)
                <a href="{{ route('admin.pesanan.index', array_filter(['tipe' => $tipeOpsi, 'status' => $status])) }}"
                   @if ($tipe === $tipeOpsi) aria-current="page" @endif
                   class="rounded-full px-4 py-1.5 text-sm font-medium {{ $tipe === $tipeOpsi ? 'bg-brand-700 text-white' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                    {{ $labelTipe }}
                </a>
            @endforeach

            <form method="GET" action="{{ route('admin.pesanan.index') }}" class="ml-auto flex items-center gap-2">
                <input type="hidden" name="tipe" value="{{ $tipe }}">
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    @foreach ($daftarStatus as $nilai => $labelStatus)
                        <option value="{{ $nilai }}" @selected($status === $nilai)>{{ $labelStatus }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">Tampilkan</button></noscript>
            </form>
        </div>

        <section aria-label="Daftar pesanan" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @if ($pesanan->isEmpty())
                <p class="px-6 py-14 text-center text-sm text-gray-500">Belum ada pesanan untuk filter ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[52rem] text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-medium">Kode / Pelanggan</th>
                                <th scope="col" class="px-3 py-3 font-medium">Jenis</th>
                                <th scope="col" class="px-3 py-3 font-medium">Pembayaran</th>
                                <th scope="col" class="px-3 py-3 font-medium">Status</th>
                                <th scope="col" class="px-3 py-3 font-medium">Tanggal</th>
                                <th scope="col" class="px-3 py-3 text-right font-medium">Nilai</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($pesanan as $item)
                                @php
                                    $ruteDetail = $item instanceof \App\Models\PesananJasa
                                        ? 'admin.pesanan.jasa.show'
                                        : 'admin.pesanan.produk.show';
                                @endphp
                                <tr>
                                    <td class="px-5 py-3.5">
                                        <span class="block font-medium text-gray-900">{{ $item->kode }}</span>
                                        <span class="mt-1 block text-xs text-gray-500">{{ $item->customer?->akun?->nama ?? '-' }}</span>
                                    </td>
                                    <td class="px-3 py-3.5 text-gray-600">{{ $item instanceof \App\Models\PesananJasa ? 'Jasa' : 'Produk' }}</td>
                                    <td class="px-3 py-3.5">
                                        <x-status-badge :nilai="$item->status_pembayaran" />
                                    </td>
                                    <td class="px-3 py-3.5">
                                        @if (isset($item->status_pesanan) && $item->status_pesanan)
                                            <x-status-badge :nilai="$item->status_pesanan" />
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 text-gray-600">{{ $item->tanggal_pesan?->format('d/m/Y') }}</td>
                                    <td class="whitespace-nowrap px-3 py-3.5 text-right font-medium text-gray-800">Rp {{ number_format($item->total_bayar, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center justify-end gap-3">
                                            @if ($item->status_pembayaran === 'menunggu_verifikasi')
                                                <form method="POST" action="{{ $ruteDetail === 'admin.pesanan.jasa.show' ? route('admin.pesanan.jasa.aksi', $item) : route('admin.pesanan.produk.aksi', $item) }}">
                                                    @csrf
                                                    <input type="hidden" name="aksi" value="verifikasi_bayar">
                                                    <button type="submit" class="font-medium text-brand-800 hover:text-brand-900">Verifikasi</button>
                                                </form>
                                            @endif
                                            <a href="{{ route($ruteDetail, $item) }}" class="font-medium text-brand-800 hover:text-brand-900">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $pesanan->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
