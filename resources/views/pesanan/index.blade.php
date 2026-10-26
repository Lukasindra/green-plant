@extends('layouts.app')

@section('judul', 'Pesanan Saya')

@section('isi')
<h1 class="mb-4 text-xl font-semibold">Pesanan Saya</h1>

@if ($pesanan->count())
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
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
                        <td class="px-4 py-3">Rp {{ number_format($p->total_bayar, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><x-status-badge :nilai="$p->status_pembayaran" /></td>
                        <td class="px-4 py-3"><x-status-badge :nilai="$p->status_pesanan" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('pesanan.show', $p) }}" class="font-medium text-brand-700 hover:underline">Detail</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pesanan->links() }}</div>
@else
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
        <p class="text-sm text-gray-500">Belum ada pesanan.</p>
        <a href="{{ route('produk.index') }}" class="mt-4 inline-block rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Mulai Belanja</a>
    </div>
@endif
@endsection
