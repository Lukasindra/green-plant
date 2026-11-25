@extends('layouts.invoice')

@section('judul', 'Invoice ' . ($pesanan->invoice_number ?? $pesanan->kode))

@section('isi')
@php
    $akun = $pesanan->customer?->akun;
    $alamat = $tipe === 'jasa' ? $pesanan->alamat_lokasi : $pesanan->alamat_pengiriman;
    $subtotal = $pesanan->detail->sum('subtotal');
    $ongkir = $tipe === 'produk' ? (int) $pesanan->ongkir : 0;
@endphp

<div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 pb-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-brand-700">Green Plant</p>
        <h1 class="mt-1 text-2xl font-semibold">Invoice</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $pesanan->invoice_number ?? $pesanan->kode }}</p>
    </div>
    <div class="text-right text-sm">
        <p class="font-medium">{{ $pesanan->tanggal_pesan?->translatedFormat('d M Y') }}</p>
        <p class="text-gray-500">Kode: {{ $pesanan->kode }}</p>
        <p class="mt-1">
            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $pesanan->status_pembayaran === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                {{ $pesanan->status_pembayaran === 'paid' ? 'LUNAS' : strtoupper($pesanan->status_pembayaran) }}
            </span>
        </p>
    </div>
</div>

<div class="grid gap-6 py-6 sm:grid-cols-2">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ditagihkan kepada</p>
        <p class="mt-1 text-sm font-medium">{{ $akun?->nama ?? 'Pelanggan' }}</p>
        @if ($akun?->no_hp)
            <p class="text-sm text-gray-600">{{ $akun->no_hp }}</p>
        @endif
        @if ($akun?->email)
            <p class="text-sm text-gray-600">{{ $akun->email }}</p>
        @endif
        <p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $alamat }}</p>
    </div>
    <div class="sm:text-right">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pembayaran</p>
        <p class="mt-1 text-sm">Midtrans Snap</p>
        @if ($pesanan->midtrans_payment_type)
            <p class="text-sm text-gray-600">{{ strtoupper($pesanan->midtrans_payment_type) }}</p>
        @endif
        @if ($pesanan->midtrans_transaction_id)
            <p class="text-xs text-gray-500">ID transaksi: {{ $pesanan->midtrans_transaction_id }}</p>
        @endif
        @if ($pesanan->tanggal_bayar)
            <p class="mt-1 text-sm text-gray-600">Dibayar {{ $pesanan->tanggal_bayar->translatedFormat('d M Y') }}</p>
        @endif
    </div>
</div>

<table class="w-full border-collapse text-sm">
    <thead>
        <tr class="border-y border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
            <th class="px-3 py-2 font-medium">Item</th>
            <th class="px-3 py-2 text-center font-medium">Qty</th>
            <th class="px-3 py-2 text-right font-medium">Harga</th>
            <th class="px-3 py-2 text-right font-medium">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($pesanan->detail as $d)
            <tr class="border-b border-gray-100">
                <td class="px-3 py-3">{{ $tipe === 'jasa' ? ($d->jasa?->nama ?? 'Jasa') : ($d->produk?->nama ?? 'Produk') }}</td>
                <td class="px-3 py-3 text-center">{{ $d->qty }}</td>
                <td class="px-3 py-3 text-right">Rp {{ number_format((int) $d->harga, 0, ',', '.') }}</td>
                <td class="px-3 py-3 text-right">Rp {{ number_format((int) $d->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-4 ml-auto max-w-xs space-y-1 text-sm">
    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>Rp {{ number_format((int) $subtotal, 0, ',', '.') }}</span></div>
    @if ($tipe === 'produk')
        <div class="flex justify-between"><span class="text-gray-500">Ongkir</span><span>Rp {{ number_format($ongkir, 0, ',', '.') }}</span></div>
    @endif
    <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold">
        <span>Total</span><span class="text-brand-700">Rp {{ number_format((int) $pesanan->total_bayar, 0, ',', '.') }}</span>
    </div>
</div>

<p class="mt-8 border-t border-gray-200 pt-4 text-xs text-gray-500">
    Invoice ini dibuat otomatis oleh Green Plant. Pembayaran diproses secara aman oleh Midtrans.
</p>
@endsection
