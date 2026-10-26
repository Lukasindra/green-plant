@extends('layouts.app')

@section('judul', $jasa->nama)

@section('isi')
<div class="grid gap-6 lg:grid-cols-2">

    {{-- Detail jasa --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5">
        <div class="aspect-[16/9] overflow-hidden rounded-lg">
            <x-gambar-jasa :foto="$jasa->foto" :nama="$jasa->nama" />
        </div>

        <span class="mt-4 inline-block text-xs uppercase tracking-wide text-teal-600">{{ $jasa->kategori?->nama }}</span>
        <h1 class="text-xl font-semibold">{{ $jasa->nama }}</h1>
        <p class="mt-2 text-base font-medium text-brand-700">
            Rp {{ number_format($jasa->harga, 0, ',', '.') }}
            <span class="text-xs text-gray-400">/ {{ $jasa->satuan }}</span>
        </p>
        @if ($jasa->deskripsi)
            <p class="mt-3 text-sm leading-relaxed text-gray-600">{{ $jasa->deskripsi }}</p>
        @endif

        <a href="{{ route('jasa.index') }}" class="mt-4 inline-block text-sm text-brand-700 hover:underline">&larr; Semua jasa</a>
    </section>

    {{-- Form pemesanan --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Pesan Jasa</h2>

        @guest
            <div class="rounded-md border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                <a href="{{ route('login') }}" class="font-semibold underline">Login</a> atau
                <a href="{{ route('daftar') }}" class="font-semibold underline">daftar</a>
                sebagai customer untuk memesan jasa.
            </div>
        @elseif (! auth()->user()->isCustomer())
            <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Pemesanan jasa hanya untuk akun customer.
            </div>
        @endif

        <form method="POST" action="{{ route('jasa.pesan', $jasa) }}"
              class="mt-4 space-y-4 {{ (auth()->check() && auth()->user()->isCustomer()) ? '' : 'pointer-events-none opacity-50' }}">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="qty" class="mb-1 block text-sm font-medium">Jumlah {{ $jasa->satuan }}</label>
                    <input type="number" id="qty" name="qty" min="1" max="10" value="{{ old('qty', 1) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    @error('qty') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="jadwal" class="mb-1 block text-sm font-medium">Jadwal pengerjaan</label>
                    <input type="datetime-local" id="jadwal" name="jadwal" required
                           min="{{ now()->format('Y-m-d\TH:i') }}"
                           value="{{ old('jadwal') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    @error('jadwal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="alamat_lokasi" class="mb-1 block text-sm font-medium">Alamat lokasi pengerjaan</label>
                <textarea id="alamat_lokasi" name="alamat_lokasi" rows="2" required
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">{{ old('alamat_lokasi', $alamat) }}</textarea>
                @error('alamat_lokasi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="catatan" class="mb-1 block text-sm font-medium">Catatan <span class="text-xs text-gray-400">(opsional)</span></label>
                <textarea id="catatan" name="catatan" rows="2"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">{{ old('catatan') }}</textarea>
                @error('catatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="mb-2 block text-sm font-medium">Metode pembayaran</span>
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
            </div>

            <button type="submit" class="w-full rounded-lg bg-brand-700 py-3 text-sm font-semibold text-white hover:bg-brand-800">
                Pesan Sekarang
            </button>
            <p class="text-center text-xs text-gray-500">Pembayaran dikonfirmasi manual oleh admin setelah bukti diunggah.</p>
        </form>
    </section>
</div>
@endsection
