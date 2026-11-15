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
                <p class="text-xs text-gray-500">Berpakai untuk pesanan produk checkout. Saat ini: <strong>{{ $formatUang($ongkir) }}</strong></p>
                <div>
                    <label for="ongkir" class="mb-1 block text-sm font-medium text-gray-700">Ongkir per pesanan</label>
                    <input type="number" id="ongkir" name="ongkir" min="0" step="500" required
                        value="{{ old('ongkir', $ongkir) }}"
                        class="w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    @error('ongkir') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            {{-- Rekening --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-gray-900">Rekening Transfer</legend>
                <p class="text-xs text-gray-500">Kosongkan baris untuk menghapus. Maksimal 5 baris.</p>
                @error('rekening') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @foreach (range(0, 4) as $i)
                    @php $baris = old('rekening.'.$i, $rekening[$i] ?? ['bank' => '', 'nomor' => '', 'nama' => '']); @endphp
                    <div class="grid grid-cols-3 gap-2" aria-label="Rekening {{ $i + 1 }}">
                        <input type="text" name="rekening[{{ $i }}][bank]" value="{{ $baris['bank'] }}" placeholder="Bank"
                            aria-label="Nama bank {{ $i + 1 }}"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        <input type="text" name="rekening[{{ $i }}][nomor]" value="{{ $baris['nomor'] }}" placeholder="Nomor"
                            aria-label="Nomor rekening {{ $i + 1 }}"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        <input type="text" name="rekening[{{ $i }}][nama]" value="{{ $baris['nama'] }}" placeholder="Nama pemilik"
                            aria-label="Nama pemilik rekening {{ $i + 1 }}"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    </div>
                @endforeach
            </fieldset>

            {{-- E-Wallet --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-gray-900">E-Wallet</legend>
                <p class="text-xs text-gray-500">Kosongkan baris untuk menghapus. Maksimal 5 baris.</p>
                @error('ewallet') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @foreach (range(0, 4) as $i)
                    @php $baris = old('ewallet.'.$i, $ewallet[$i] ?? ['nama' => '', 'nomor' => '']); @endphp
                    <div class="grid grid-cols-2 gap-2" aria-label="E-Wallet {{ $i + 1 }}">
                        <input type="text" name="ewallet[{{ $i }}][nama]" value="{{ $baris['nama'] }}" placeholder="Nama e-wallet"
                            aria-label="Nama e-wallet {{ $i + 1 }}"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        <input type="text" name="ewallet[{{ $i }}][nomor]" value="{{ $baris['nomor'] }}" placeholder="Nomor"
                            aria-label="Nomor e-wallet {{ $i + 1 }}"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    </div>
                @endforeach
            </fieldset>

            <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
@endsection
