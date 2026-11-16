@extends('layouts.admin')

@section('judul', 'Kelola Kategori')

@section('isi')
    @php
        $tab = [
            null => 'Semua',
            'produk' => 'Produk',
            'jasa' => 'Jasa',
        ];
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-gray-500">Kelompokkan produk dan jasa agar mudah dicari customer.</p>
        <x-button href="{{ route('admin.kategori.create') }}">+ Tambah Kategori</x-button>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @foreach ($tab as $kunci => $label)
            <a href="{{ route('admin.kategori.index', $kunci ? ['tipe' => $kunci] : []) }}"
               @if ($tipe === $kunci) aria-current="page" @endif
               class="rounded-full px-4 py-1.5 text-sm font-medium {{ $tipe === $kunci ? 'bg-brand-700 text-white' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <x-panel>
        @if ($kategori->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="font-semibold text-gray-800">Belum ada kategori.</p>
                <a href="{{ route('admin.kategori.create') }}" class="mt-3 inline-flex text-sm font-semibold text-brand-800 hover:text-brand-900">Tambah kategori pertama</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[36rem] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Nama</th>
                            <th scope="col" class="px-3 py-3 font-medium">Tipe</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Dipakai</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($kategori as $item)
                            @php $dipakai = $item->tipe === 'produk' ? $item->produk_count : $item->jasa_count; @endphp
                            <tr>
                                <td class="px-5 py-3 font-semibold text-gray-900">{{ $item->nama }}</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $item->tipe === 'produk' ? 'bg-brand-100 text-brand-800' : 'bg-blue-100 text-blue-800' }}">{{ ucfirst($item->tipe) }}</span>
                                </td>
                                <td class="px-3 py-3 text-right text-gray-600">{{ $dipakai }} {{ $item->tipe }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.kategori.edit', $item) }}" class="font-medium text-brand-800 hover:text-brand-900">Edit</a>
                                        <form method="POST" action="{{ route('admin.kategori.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus kategori {{ $item->nama }}?')">
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
            @if ($kategori->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $kategori->links() }}
                </div>
            @endif
        @endif
    </x-panel>
@endsection
