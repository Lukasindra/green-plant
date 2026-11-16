@extends('layouts.admin')

@section('judul', $kategori ? 'Ubah Kategori' : 'Tambah Kategori')

@section('isi')
    <div class="mx-auto max-w-xl">
        <form method="POST" action="{{ $kategori ? route('admin.kategori.update', $kategori) : route('admin.kategori.store') }}"
            class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @if ($kategori)
                @method('PUT')
            @endif

            @if ($kategori)
                <div>
                    <p class="mb-1 text-sm font-medium text-gray-700">Tipe</p>
                    <p class="text-sm text-gray-900">{{ ucfirst($kategori->tipe) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Tipe tidak dapat diubah karena produk atau jasa yang memakainya akan ikut berpindah.</p>
                </div>
            @else
                <x-select nama="tipe" label="Tipe" wajib>
                    @foreach ($daftarTipe as $pilihan)
                        <option value="{{ $pilihan }}" @selected(old('tipe', request('tipe')) === $pilihan)>{{ ucfirst($pilihan) }}</option>
                    @endforeach
                </x-select>
            @endif

            <x-input nama="nama" label="Nama kategori" wajib maxlength="100" value="{{ old('nama', $kategori->nama ?? '') }}" />

            <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                <x-button>{{ $kategori ? 'Simpan Perubahan' : 'Simpan Kategori' }}</x-button>
                <a href="{{ route('admin.kategori.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Batal</a>
            </div>
        </form>
    </div>
@endsection
