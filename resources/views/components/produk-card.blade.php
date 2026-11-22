@props(['produk', 'favoritProdukIds' => []])
@php
    $isFavorite = in_array($produk->getKey(), $favoritProdukIds, true);
@endphp
<div class="relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <a href="{{ route('produk.show', $produk) }}" class="block aspect-square bg-gray-50" title="Lihat detail {{ $produk->nama }}">
        <x-gambar-produk :foto="$produk->foto" :nama="$produk->nama" />
        @if ($produk->stok === 0)
            <span class="absolute left-2 top-2 rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-semibold text-white">Habis</span>
        @elseif ($produk->stok <= 5)
            <span class="absolute left-2 top-2 rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-semibold text-white">Tersisa {{ number_format($produk->stok) }}</span>
        @else
            <span class="absolute left-2 top-2 rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">Tersedia {{ number_format($produk->stok) }} unit</span>
        @endif
    </a>
    @if (auth()->guest())
        <a href="{{ route('login') }}" aria-label="Masuk untuk menyimpan {{ $produk->nama }} ke favorit"
            title="Masuk untuk menyimpan ke favorit"
            class="absolute right-2 top-2 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white text-gray-500 shadow-sm transition hover:text-red-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z" />
            </svg>
        </a>
    @elseif (auth()->user()->isCustomer())
        <form method="POST"
            action="{{ $isFavorite ? route('favorit.produk.destroy', $produk) : route('favorit.produk.store', $produk) }}"
            class="absolute right-2 top-2 z-10">
            @csrf
            @if ($isFavorite)
                @method('DELETE')
            @endif
            <button type="submit"
                aria-label="{{ $isFavorite ? 'Hapus ' . $produk->nama . ' dari favorit' : 'Simpan ' . $produk->nama . ' ke favorit' }}"
                aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"
                title="{{ $isFavorite ? 'Hapus dari favorit' : 'Simpan ke favorit' }}"
                class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700 {{ $isFavorite ? 'text-red-600 hover:text-red-700' : 'text-gray-500 hover:text-red-600' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="{{ $isFavorite ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z" />
                </svg>
            </button>
        </form>
    @endif
    <div class="flex flex-1 flex-col p-3">
        <h3 class="text-sm font-semibold text-gray-900">
            <a href="{{ route('produk.show', $produk) }}" class="hover:text-brand-700">{{ $produk->nama }}</a>
        </h3>
        <p class="text-xs text-gray-500">{{ $produk->kategori?->nama }}</p>
        <p class="mt-2 text-sm font-semibold text-gray-900">Rp {{ number_format($produk->harga, 0, ',', '.') }}</p>

        @if ($produk->stok > 0)
            <form method="POST" action="{{ route('keranjang.tambah') }}" class="mt-3">
                @csrf
                <input type="hidden" name="produk_id" value="{{ $produk->id }}">
                <button type="submit" class="w-full rounded-md bg-brand-700 py-2 text-xs font-medium text-white transition hover:bg-brand-800">
                    + Keranjang
                </button>
            </form>
        @else
            <button type="button" disabled class="mt-3 w-full cursor-not-allowed rounded-md bg-gray-200 py-2 text-xs font-medium text-gray-500">
                Stok habis
            </button>
        @endif
    </div>
</div>
