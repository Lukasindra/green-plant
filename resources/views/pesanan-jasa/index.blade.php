@extends('layouts.app')

@section('judul', 'Pesanan Jasa Saya')

@section('isi')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold">Pesanan Saya</h1>
    <div class="text-sm">
        <a href="{{ route('pesanan.index') }}" class="text-gray-500 hover:text-brand-700">Produk</a>
        <span class="mx-1 text-gray-400">|</span>
        <span class="border-b-2 border-brand-700 py-1 font-medium text-brand-700">Jasa</span>
    </div>
</div>

@if ($pesanan->count())
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Jadwal</th>
                    <th class="px-4 py-3 font-medium">Total</th>
                    <th class="px-4 py-3 font-medium">Pembayaran</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($pesanan as $p)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $p->kode }}</td>
                        <td class="px-4 py-3">{{ $p->tanggal_pesan->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $p->jadwal_pengerjaan?->format('d M Y, H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($p->total_bayar, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><x-status-badge :nilai="$p->status_pembayaran" /></td>
                        <td class="px-4 py-3"><x-status-badge :nilai="$p->status_pesanan" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('pesanan-jasa.show', $p) }}" class="font-medium text-brand-700 hover:underline">Detail</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pesanan->links() }}</div>
@else
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
        <p class="text-sm text-gray-500">Belum ada pesanan jasa.</p>
        <a href="{{ route('jasa.index') }}" class="mt-4 inline-block rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Lihat Jasa</a>
    </div>
@endif
@endsection
