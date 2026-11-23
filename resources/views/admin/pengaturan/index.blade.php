@extends('layouts.admin')

@section('judul', 'Pengaturan')

@section('isi')
    @php
        $formatUang = fn ($nilai) => 'Rp ' . number_format((float) $nilai, 0, ',', '.');
        $kunciSumber = \Illuminate\Support\Facades\Schema::hasTable('pengaturan');
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        @if (! $kunciSumber)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
                Perubahan disimpan ke tabel <code>pengaturan</code>; tabel belum ada sehingga tampilan memakai nilai bawaan <code>config/toko.php</code>.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pengaturan.simpan') }}"
            class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            {{-- Ongkir --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-gray-900">Biaya Antar (Ongkir)</legend>
                <p class="text-xs text-gray-500">Berlaku untuk pesanan produk checkout. Saat ini: <strong>{{ $formatUang($ongkir) }}</strong></p>
                <div>
                    <label for="ongkir" class="mb-1 block text-sm font-medium text-gray-700">Ongkir per pesanan</label>
                    <input type="number" id="ongkir" name="ongkir" min="0" step="500" required
                        value="{{ old('ongkir', $ongkir) }}"
                        class="w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    @error('ongkir') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            {{-- Midtrans --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-gray-900">Pembayaran Midtrans</legend>
                <p class="text-xs text-gray-500">
                    Semua pembayaran diproses Midtrans Snap (QRIS, e-wallet, transfer bank, kartu), jadi rekening dan e-wallet toko tidak perlu diisi lagi.
                    Kredensial Midtrans dikelola lewat file <code>.env</code> (<code>MIDTRANS_CLIENT_KEY</code>, <code>MIDTRANS_SERVER_KEY</code>, <code>MIDTRANS_IS_PRODUCTION</code>).
                </p>
            </fieldset>

            <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                <x-button>Simpan Pengaturan</x-button>
            </div>
        </form>
    </div>
@endsection
