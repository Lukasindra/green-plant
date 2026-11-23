@extends('layouts.app')

@section('judul', 'Jasa')

@section('isi')
<div class="grid gap-6 md:grid-cols-[220px_1fr]">

    {{-- Sidebar filter --}}
    <aside class="rounded-xl border border-gray-200 bg-white p-4">
        <h2 class="mb-3 text-sm font-semibold">Kategori</h2>
        <ul class="space-y-1 text-sm">
            <li>
                <a href="{{ route('jasa.index', array_filter(request()->except(['page', 'kategori']))) }}"
                   class="block rounded-md px-3 py-2 {{ blank($kategoriAktif) ? 'bg-brand-100 font-medium text-brand-800' : 'text-gray-600 hover:bg-gray-100' }}">
                    Semua Kategori
                </a>
            </li>
            @foreach ($kategori as $k)
                <li>
                    <a href="{{ route('jasa.index', array_merge(request()->except(['page', 'kategori']), ['kategori' => $k->id])) }}"
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
            @if (filled($q))
                <div class="mt-2 flex items-center gap-2">
                    <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800">Pencarian: &ldquo;{{ $q }}&rdquo;</span>
                    <a href="{{ route('jasa.index', array_filter(request()->except(['page', 'q']))) }}"
                        class="text-xs text-gray-500 hover:text-brand-700">Hapus &times;</a>
                </div>
            @endif
        </div>

        @if ($jasa->count())
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($jasa as $j)
                    <x-jasa-card :jasa="$j" />
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
