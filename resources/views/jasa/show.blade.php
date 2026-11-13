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

            <div class="grid gap-3 sm:grid-cols-2">
                <x-input nama="qty" label="Jumlah {{ $jasa->satuan }}" tipe="number" required min="1" max="10" value="{{ old('qty', 1) }}" />
                <x-input nama="jadwal" label="Jadwal pengerjaan" tipe="datetime-local" required
                    min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('jadwal') }}" />
            </div>

            <x-textarea nama="alamat_lokasi" label="Alamat lokasi pengerjaan" :baris="2" required>{{ old('alamat_lokasi', $alamat) }}</x-textarea>

            <x-textarea nama="catatan" label="Catatan (opsional)" :baris="2">{{ old('catatan') }}</x-textarea>

            <div>
                <span class="mb-2 block text-sm font-medium">Metode pembayaran</span>
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
            </div>

            <x-button class="w-full py-3">Pesan Sekarang</x-button>
            <p class="text-center text-xs text-gray-500">Pembayaran dikonfirmasi manual oleh admin setelah bukti diunggah.</p>
        </form>
    </section>
</div>
@endsection
