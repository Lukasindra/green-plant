@extends('layouts.app')

@section('judul', 'Keranjang')

@section('isi')
<h1 class="mb-4 text-xl font-semibold">Keranjang Belanja</h1>

@if (count($baris))
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        {{-- Judul kolom (layar lebar) --}}
        <div class="hidden grid-cols-[1fr_110px_140px_120px_50px] gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-medium text-gray-500 md:grid">
            <span>Produk</span><span>Harga</span><span>Jumlah</span><span>Subtotal</span><span>Aksi</span>
        </div>

        @foreach ($baris as $b)
            @php $p = $b['produk']; @endphp
            <div class="grid grid-cols-2 items-center gap-3 border-b border-gray-100 px-4 py-4 last:border-b-0 md:grid-cols-[1fr_110px_140px_120px_50px]">

                {{-- Produk --}}
                <div class="col-span-2 flex items-center gap-3 md:col-span-1">
                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-gray-50">
                        <x-gambar-produk :foto="$p->foto" :nama="$p->nama" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold">{{ $p->nama }}</p>
                        <p class="text-xs text-gray-500">{{ $p->kategori?->nama }}</p>
                    </div>
                </div>

                {{-- Harga --}}
                <p class="text-sm">Rp {{ number_format($p->harga, 0, ',', '.') }}</p>

                {{-- Jumlah --}}
                <div class="flex items-center gap-1">
                    <form method="POST" action="{{ route('keranjang.ubah') }}">
                        @csrf
                        <input type="hidden" name="produk_id" value="{{ $p->id }}">
                        <input type="hidden" name="aksi" value="kurang">
                        <button type="submit" aria-label="Kurangi" class="h-7 w-7 rounded border border-gray-300 text-sm hover:bg-gray-100">&minus;</button>
                    </form>
                    <span class="w-8 text-center text-sm">{{ $b['qty'] }}</span>
                    <form method="POST" action="{{ route('keranjang.ubah') }}">
                        @csrf
                        <input type="hidden" name="produk_id" value="{{ $p->id }}">
                        <input type="hidden" name="aksi" value="tambah">
                        <button type="submit" aria-label="Tambah" class="h-7 w-7 rounded border border-gray-300 text-sm hover:bg-gray-100">+</button>
                    </form>
                </div>

                {{-- Subtotal --}}
                <p class="text-sm font-medium">Rp {{ number_format($b['subtotal'], 0, ',', '.') }}</p>

                {{-- Hapus --}}
                <form method="POST" action="{{ route('keranjang.hapus', $p) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" aria-label="Hapus {{ $p->nama }}" class="text-gray-400 hover:text-red-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3"/></svg>
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <div class="mt-4 flex items-center justify-end gap-6">
        <span class="text-sm text-gray-600">Total Belanja</span>
        <span class="text-lg font-semibold text-brand-700">Rp {{ number_format($total, 0, ',', '.') }}</span>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
        <a href="{{ route('produk.index') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-medium hover:bg-gray-50">
            &larr; Lanjut Belanja
        </a>

        @auth
            <a href="{{ route('checkout.index') }}" class="rounded-lg bg-brand-700 px-8 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-800">Checkout</a>
        @else
            <a href="{{ route('login') }}" class="rounded-lg bg-brand-700 px-8 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-800">Login untuk Checkout</a>
        @endauth
    </div>
@else
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
        <p class="text-sm text-gray-500">Keranjang Anda masih kosong.</p>
        <a href="{{ route('produk.index') }}" class="mt-4 inline-block rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Mulai Belanja</a>
    </div>
@endif
@endsection
