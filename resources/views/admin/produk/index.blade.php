@extends('layouts.admin')

@section('judul', 'Kelola Produk')

@section('isi')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-gray-500">Kelola daftar produk yang dijual: tambah, ubah, atau hapus.</p>
        <a href="{{ route('admin.produk.create') }}" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
            + Tambah Produk
        </a>
    </div>

    <section aria-label="Daftar produk" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @if ($produk->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="font-semibold text-gray-800">Belum ada produk.</p>
                <a href="{{ route('admin.produk.create') }}" class="mt-3 inline-flex text-sm font-semibold text-brand-800 hover:text-brand-900">Tambah produk pertama</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Produk</th>
                            <th scope="col" class="px-3 py-3 font-medium">Kategori</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Harga</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Stok</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($produk as $item)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-gray-50">
                                            <x-gambar-produk :foto="$item->foto" :nama="$item->nama" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ $item->nama }}</p>
                                            <p class="mt-0.5 max-w-xs truncate text-xs text-gray-500">{{ $item->deskripsi }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-gray-600">{{ $item->kategori?->nama ?? 'Tanpa kategori' }}</td>
                                <td class="px-3 py-3 text-right text-gray-700">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-gray-700">{{ number_format($item->stok) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.produk.edit', $item) }}" class="font-medium text-brand-800 hover:text-brand-900">Edit</a>
                                        <form method="POST" action="{{ route('admin.produk.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus produk {{ $item->nama }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-700 hover:text-red-800">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $produk->links() }}
            </div>
        @endif
    </section>
@endsection
