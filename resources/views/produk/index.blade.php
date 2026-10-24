@extends('layouts.app')

@section('judul', 'Produk')

@section('isi')
<div class="grid gap-6 md:grid-cols-[220px_1fr]">

    {{-- Sidebar filter --}}
    <aside class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold">Kategori</h2>
            <ul class="space-y-1 text-sm">
                <li>
                    <a href="{{ route('produk.index', request()->except(['page', 'kategori'])) }}"
                       class="block rounded-md px-3 py-2 {{ blank($kategoriAktif) ? 'bg-brand-100 font-medium text-brand-800' : 'text-gray-600 hover:bg-gray-100' }}">
                        Semua Kategori
                    </a>
                </li>
                @foreach ($kategori as $k)
                    <li>
                        <a href="{{ route('produk.index', array_merge(request()->except('page'), ['kategori' => $k->id])) }}"
                           class="block rounded-md px-3 py-2 {{ $kategoriAktif == $k->id ? 'bg-brand-100 font-medium text-brand-800' : 'text-gray-600 hover:bg-gray-100' }}">
                            {{ $k->nama }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <form method="GET" action="{{ route('produk.index') }}" class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold">Rentang Harga</h2>
            @if (filled($kategoriAktif)) <input type="hidden" name="kategori" value="{{ $kategoriAktif }}"> @endif
            @if (filled($urut)) <input type="hidden" name="urut" value="{{ $urut }}"> @endif

            <input type="range" name="maks" min="0" max="1000000" step="5000" value="{{ $maks }}"
                   class="w-full accent-brand-700"
                   oninput="document.getElementById('label-maks').textContent = 'Rp ' + Number(this.value).toLocaleString('id-ID')">
            <div class="mt-1 flex justify-between text-xs text-gray-500">
                <span>Rp 0</span>
                <span id="label-maks">Rp {{ number_format($maks, 0, ',', '.') }}</span>
            </div>
            <button type="submit" class="mt-3 w-full rounded-md bg-brand-700 py-2 text-sm font-medium text-white hover:bg-brand-800">Filter</button>
        </form>
    </aside>

    {{-- Daftar produk --}}
    <section>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Produk</h1>
            <form method="GET" action="{{ route('produk.index') }}" class="text-sm">
                @if (filled($kategoriAktif)) <input type="hidden" name="kategori" value="{{ $kategoriAktif }}"> @endif
                <input type="hidden" name="maks" value="{{ $maks }}">
                <select name="urut" onchange="this.form.submit()" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm">
                    <option value="terbaru"  @selected($urut === 'terbaru')>Urutkan: Terbaru</option>
                    <option value="termurah" @selected($urut === 'termurah')>Harga terendah</option>
                    <option value="termahal" @selected($urut === 'termahal')>Harga tertinggi</option>
                </select>
            </form>
        </div>

        @if ($produk->count())
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($produk as $p)
                    <x-produk-card :produk="$p" />
                @endforeach
            </div>
            <div class="mt-6">{{ $produk->links() }}</div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                Tidak ada produk yang cocok dengan filter ini.
            </div>
        @endif
    </section>
</div>
@endsection
