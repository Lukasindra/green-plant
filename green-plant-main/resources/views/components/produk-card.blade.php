@props(['produk'])
<div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="aspect-square bg-gray-50">
        <x-gambar-produk :foto="$produk->foto" :nama="$produk->nama" />
    </div>
    <div class="flex flex-1 flex-col p-3">
        <h3 class="text-sm font-semibold text-gray-900">{{ $produk->nama }}</h3>
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
