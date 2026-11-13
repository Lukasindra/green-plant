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

    {{-- Metode pembayaran --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Pilih Metode Pembayaran</h2>

        <div class="space-y-2" role="radiogroup" aria-label="Metode pembayaran">
            @foreach (config('toko.metode') as $kode => $nama)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm hover:bg-gray-50 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                    <input type="radio" name="metode_pembayaran" value="{{ $kode }}" class="accent-brand-700"
                           @checked(old('metode_pembayaran', 'transfer') === $kode) required>
                    <span>{{ $nama }}</span>
                </label>
            @endforeach
        </div>
        @error('metode_pembayaran') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror

        <x-button class="mt-5 w-full py-3">Buat Pesanan</x-button>
        <p class="mt-2 text-center text-xs text-gray-500">Pembayaran dikonfirmasi manual oleh admin setelah bukti diunggah.</p>
    </section>
</form>
@endsection
