@extends('layouts.app')

@section('judul', $produk->nama)

@section('isi')
<a href="{{ route('produk.index') }}" class="mb-4 inline-flex text-sm text-brand-700 hover:underline">&larr; Kembali ke katalog</a>

<div class="grid gap-6 lg:grid-cols-2">
    {{-- Gambar --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5">
        <div class="aspect-square overflow-hidden rounded-lg bg-gray-50">
            <x-gambar-produk :foto="$produk->foto" :nama="$produk->nama" />
        </div>
    </section>

    {{-- Info & aksi --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <span class="text-xs uppercase tracking-wide text-teal-600">{{ $produk->kategori?->nama ?? 'Tanpa kategori' }}</span>
        <h1 class="mt-1 text-2xl font-semibold">{{ $produk->nama }}</h1>
        <p class="mt-2 text-xl font-medium text-brand-700">Rp {{ number_format($produk->harga, 0, ',', '.') }}</p>

        @if ($produk->deskripsi)
            <p class="mt-3 text-sm leading-relaxed text-gray-600">{{ $produk->deskripsi }}</p>
        @endif

        @if ($produk->stok > 0)
            <p class="mt-3 inline-flex rounded-full bg-lime-50 px-3 py-1 text-xs font-medium text-lime-700">Tersisa {{ number_format($produk->stok) }}</p>
        @else
            <p class="mt-3 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500">Stok habis</p>
        @endif

        <form method="POST" action="{{ route('keranjang.tambah') }}" class="mt-5 border-t border-gray-200 pt-4">
            @csrf
            <input type="hidden" name="produk_id" value="{{ $produk->id }}">
            @if ($produk->stok > 0)
                <x-button class="w-full py-3">Tambah ke Keranjang</x-button>
                <p class="mt-2 text-center text-xs text-gray-500">Jumlah bisa diatur di halaman keranjang.</p>
            @else
                <x-button class="w-full py-3" disabled>Stok Habis</x-button>
            @endif
        </form>
    </section>
</div>

@if ($terkait->isNotEmpty())
    <section class="mt-8">
        <h2 class="mb-4 text-lg font-semibold">Produk Serupa</h2>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($terkait as $t)
                <x-produk-card :produk="$t" />
            @endforeach
        </div>
    </section>
@endif
@endsection
