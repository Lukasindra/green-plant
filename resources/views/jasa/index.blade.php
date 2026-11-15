@extends('layouts.app')

@section('judul', 'Jasa')

@section('isi')
<div class="grid gap-6 md:grid-cols-[220px_1fr]">

    {{-- Sidebar filter --}}
    <aside class="rounded-xl border border-gray-200 bg-white p-4">
        <h2 class="mb-3 text-sm font-semibold">Kategori</h2>
        <ul class="space-y-1 text-sm">
            <li>
                <a href="{{ route('jasa.index') }}"
                   class="block rounded-md px-3 py-2 {{ blank($kategoriAktif) ? 'bg-brand-100 font-medium text-brand-800' : 'text-gray-600 hover:bg-gray-100' }}">
                    Semua Kategori
                </a>
            </li>
            @foreach ($kategori as $k)
                <li>
                    <a href="{{ route('jasa.index', ['kategori' => $k->id]) }}"
                       class="block rounded-md px-3 py-2 {{ $kategoriAktif == $k->id ? 'bg-brand-100 font-medium text-brand-800' : 'text-gray-600 hover:bg-gray-100' }}">
                        {{ $k->nama }}
                    </a>
                </li>
            @endforeach
        </ul>
    </aside>

    {{-- Daftar jasa --}}
    <section>
        <div class="mb-4">
            <h1 class="text-xl font-semibold">Jasa</h1>
            <p class="text-sm text-gray-500">Pilih jasa perawatan taman dan atur jadwal pengerjaannya.</p>
        </div>

        @if ($jasa->count())
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($jasa as $j)
                    <a href="{{ route('jasa.show', $j) }}"
                       class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="aspect-[4/3] overflow-hidden">
                            <x-gambar-jasa :foto="$j->foto" :nama="$j->nama" />
                        </div>
                        <div class="flex flex-1 flex-col gap-1 p-4">
                            <span class="text-xs uppercase tracking-wide text-teal-600">{{ $j->kategori?->nama }}</span>
                            <span class="font-semibold text-gray-900 group-hover:text-brand-700">{{ $j->nama }}</span>
                            <span class="mt-auto pt-2 text-sm font-medium text-brand-700">
                                Rp {{ number_format($j->harga, 0, ',', '.') }}
                                <span class="text-xs text-gray-400">/ {{ $j->satuan }}</span>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $jasa->links() }}</div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                Tidak ada jasa yang cocok dengan filter ini.
            </div>
        @endif
    </section>
</div>
@endsection
