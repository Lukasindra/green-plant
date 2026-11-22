@extends('layouts.app')

@section('judul', 'Favorit Saya')

@section('isi')
<div class="mb-4">
    <h1 class="text-xl font-semibold">Favorit Saya</h1>
    <p class="mt-1 text-sm text-gray-500">Produk yang kamu simpan untuk dilihat lagi.</p>
</div>

@if ($produk->count())
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($produk as $p)
            <x-produk-card :produk="$p" :favorit-produk-ids="$favoritProdukIds" />
        @endforeach
    </div>
    <div class="mt-6">{{ $produk->links() }}</div>
@else
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
        <p class="font-semibold text-gray-800">Belum ada produk favorit.</p>
        <x-button href="{{ route('produk.index') }}" class="mt-4">Jelajahi Produk</x-button>
    </div>
@endif
@endsection
