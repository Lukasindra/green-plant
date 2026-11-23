@extends('layouts.admin')

@section('judul', 'Pembayaran')

@section('isi')
    @php
        $daftarJenis = [
            'unpaid' => 'Belum Dibayar',
            'paid' => 'Lunas',
            'failed' => 'Pembayaran Gagal',
        ];

        $daftarTipe = [
            'semua' => 'Semua',
            'produk' => 'Produk',
            'jasa' => 'Jasa',
        ];
    @endphp

    {{-- Ringkasan --}}
    <section aria-label="Ringkasan pembayaran" class="grid gap-4 sm:grid-cols-3">
        @foreach ($ringkasan as $kunciJenis => $ringkas)
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">{{ $daftarJenis[$kunciJenis] }}</p>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($ringkas['jumlah']) }}</p>
                <p class="mt-1 text-xs text-gray-500">Nilai: Rp {{ number_format($ringkas['nilai'], 0, ',', '.') }}</p>
            </article>
        @endforeach
    </section>

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($daftarJenis as $kunciJenis => $labelJenis)
                <a href="{{ route('admin.pembayaran.index', array_filter(['tipe' => $tipe, 'jenis' => $kunciJenis])) }}"
                   @if ($jenis === $kunciJenis) aria-current="page" @endif
                   class="rounded-full px-4 py-1.5 text-sm font-medium {{ $jenis === $kunciJenis ? 'bg-brand-700 text-white' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                    {{ $labelJenis }}
                </a>
            @endforeach

            <div class="ml-auto flex flex-wrap items-center gap-2">
                @foreach ($daftarTipe as $tipeOpsi => $labelTipe)
                    <a href="{{ route('admin.pembayaran.index', array_filter(['tipe' => $tipeOpsi, 'jenis' => $jenis])) }}"
                       @if ($tipe === $tipeOpsi) aria-current="page" @endif
                       class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $tipe === $tipeOpsi ? 'border-brand-700 bg-brand-50 text-brand-800' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                        {{ $labelTipe }}
                    </a>
                @endforeach
            </div>
        </div>

        <x-panel>
            @if ($pesanan->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="font-semibold text-gray-800">Tidak ada pembayaran pada status ini.</p>
                    <a href="{{ route('admin.pembayaran.index') }}"
                        class="mt-3 inline-flex text-sm font-semibold text-brand-800 hover:text-brand-900">Lihat semua pembayaran</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[48rem] text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-medium">Invoice / Pelanggan</th>
                                <th scope="col" class="px-3 py-3 font-medium">Jenis</th>
                                <th scope="col" class="px-3 py-3 font-medium">Status</th>
                                <th scope="col" class="px-3 py-3 text-right font-medium">Nilai</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($pesanan as $item)
                                @php
                                    $adalahJasa = $item instanceof \App\Models\PesananJasa;
                                    $ruteDetail = $adalahJasa ? 'admin.pesanan.jasa.show' : 'admin.pesanan.produk.show';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3.5">
                                        <span class="block font-medium text-gray-900">{{ $item->invoice_number ?? $item->kode }}</span>
                                        <span class="mt-1 block text-xs text-gray-500">{{ $item->customer?->akun?->nama ?? '-' }} &middot; {{ $item->kode }}</span>
                                    </td>
                                    <td class="px-3 py-3.5 text-gray-600">{{ $adalahJasa ? 'Jasa' : 'Produk' }}</td>
                                    <td class="px-3 py-3.5">
                                        <x-status-badge :nilai="$item->status_pembayaran" />
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 text-right font-medium text-gray-800">Rp {{ number_format($item->total_bayar, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route($ruteDetail, $item) }}" class="font-medium text-brand-800 hover:text-brand-900">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($pesanan->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">
                        {{ $pesanan->links() }}
                    </div>
                @endif
            @endif
        </x-panel>
    </div>
@endsection
