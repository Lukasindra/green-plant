@extends('layouts.app')

@section('judul', 'Checkout')

@section('isi')
<h1 class="mb-4 text-xl font-semibold">Checkout</h1>

<form method="POST" action="{{ route('checkout.store') }}" class="grid gap-6 lg:grid-cols-2">
    @csrf

    {{-- Detail pesanan --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Detail Pesanan</h2>

        <ul class="divide-y divide-gray-100 text-sm">
            @foreach ($baris as $b)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $b['produk']->nama }} <span class="text-gray-400">x{{ $b['qty'] }}</span></span>
                    <span>Rp {{ number_format($b['subtotal'], 0, ',', '.') }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="mt-3 space-y-1 border-t border-gray-200 pt-3 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>Rp {{ number_format($subtotal, 0, ',', '.') }}</dd></div>
            <div class="flex justify-between"><dt>Ongkir</dt><dd>Rp {{ number_format($ongkir, 0, ',', '.') }}</dd></div>
            <div class="flex justify-between pt-1 text-base font-semibold">
                <dt>Total Pembayaran</dt><dd class="text-brand-700">Rp {{ number_format($total, 0, ',', '.') }}</dd>
            </div>
        </dl>

        <div class="mt-5">
            <x-textarea nama="alamat_pengiriman" label="Alamat pengiriman" :baris="3" required>{{ old('alamat_pengiriman', $alamat) }}</x-textarea>
        </div>
    </section>

    {{-- Pembayaran --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Pembayaran via Midtrans</h2>

        <div class="mb-4 grid grid-cols-2 gap-2 text-center text-xs text-gray-600 sm:grid-cols-4">
            @foreach (['QRIS', 'E-Wallet', 'Transfer Bank', 'Kartu'] as $metode)
                <span class="rounded-lg border border-gray-200 px-2 py-2">{{ $metode }}</span>
            @endforeach
        </div>

        <x-button class="w-full py-3">Buat Pesanan &amp; Bayar</x-button>
        <p class="mt-2 text-center text-xs text-gray-500">Jendela pembayaran Midtrans (Snap) akan terbuka setelah pesanan dibuat.</p>
    </section>
</form>
@endsection
