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
                    <form method="POST" action="{{ route('keranjang.ubah') }}" onchange="this.submit()">
                        @csrf
                        <input type="hidden" name="produk_id" value="{{ $p->id }}">
                        <input type="hidden" name="aksi" value="atur">
                        <label for="qty-{{ $p->id }}" class="sr-only">Jumlah {{ $p->nama }}</label>
                        <input id="qty-{{ $p->id }}" type="number" name="qty" min="1" max="{{ max(1, $p->stok) }}"
                            value="{{ $b['qty'] }}" required
                            class="w-10 rounded border border-gray-300 px-0.5 py-1 text-center text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    </form>
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
        <x-button varian="outline" href="{{ route('produk.index') }}" class="w-full sm:w-auto">&larr; Lanjut Belanja</x-button>

        @auth
            <x-button href="{{ route('checkout.index') }}" class="w-full sm:w-auto">Checkout</x-button>
        @else
            <x-button href="{{ route('login') }}" class="w-full sm:w-auto">Login untuk Checkout</x-button>
        @endauth
    </div>
@else
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
        <p class="font-semibold text-gray-800">Keranjang Anda masih kosong.</p>
        <x-button href="{{ route('produk.index') }}" class="mt-4">Mulai Belanja</x-button>
    </div>
@endif
@endsection
