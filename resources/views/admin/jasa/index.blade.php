@extends('layouts.admin')

@section('judul', 'Kelola Jasa')

@section('isi')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-gray-500">Kelola layanan perawatan tanaman: tambah, ubah, atau hapus.</p>
        <a href="{{ route('admin.jasa.create') }}" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
            + Tambah Jasa
        </a>
    </div>

    <section aria-label="Daftar jasa" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @if ($jasa->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="font-semibold text-gray-800">Belum ada jasa.</p>
                <a href="{{ route('admin.jasa.create') }}" class="mt-3 inline-flex text-sm font-semibold text-brand-800 hover:text-brand-900">Tambah jasa pertama</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Jasa</th>
                            <th scope="col" class="px-3 py-3 font-medium">Kategori</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Harga</th>
                            <th scope="col" class="px-3 py-3 font-medium">Satuan</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($jasa as $item)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-gray-50">
                                            <x-gambar-jasa :foto="$item->foto" :nama="$item->nama" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ $item->nama }}</p>
                                            <p class="mt-0.5 max-w-xs truncate text-xs text-gray-500">{{ $item->deskripsi }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-gray-600">{{ $item->kategori?->nama ?? 'Tanpa kategori' }}</td>
                                <td class="px-3 py-3 text-right text-gray-700">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                                <td class="px-3 py-3 text-gray-600">{{ $item->satuan }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.jasa.edit', $item) }}" class="font-medium text-brand-800 hover:text-brand-900">Edit</a>
                                        <form method="POST" action="{{ route('admin.jasa.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus jasa {{ $item->nama }}?')">
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
                {{ $jasa->links() }}
            </div>
        @endif
    </section>
@endsection
