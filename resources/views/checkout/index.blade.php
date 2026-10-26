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
            <label for="alamat_pengiriman" class="mb-1 block text-sm font-medium">Alamat pengiriman</label>
            <textarea id="alamat_pengiriman" name="alamat_pengiriman" rows="3" required
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">{{ old('alamat_pengiriman', $alamat) }}</textarea>
            @error('alamat_pengiriman') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </section>

    {{-- Metode pembayaran --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Pilih Metode Pembayaran</h2>

        <div class="space-y-2">
            @foreach (config('toko.metode') as $kode => $nama)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm hover:bg-gray-50 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                    <input type="radio" name="metode_pembayaran" value="{{ $kode }}" class="accent-brand-700"
                           @checked(old('metode_pembayaran', 'transfer') === $kode) required>
                    <span>{{ $nama }}</span>
                </label>
            @endforeach
        </div>
        @error('metode_pembayaran') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

        <button type="submit" class="mt-5 w-full rounded-lg bg-brand-700 py-3 text-sm font-semibold text-white hover:bg-brand-800">
            Buat Pesanan
        </button>
        <p class="mt-2 text-center text-xs text-gray-500">Pembayaran dikonfirmasi manual oleh admin setelah bukti diunggah.</p>
    </section>
</form>
@endsection
