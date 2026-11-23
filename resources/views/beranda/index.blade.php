@extends('layouts.app')

@section('judul', 'Beranda')

@section('isi')
{{-- Hero --}}
<section class="rounded-xl border border-brand-200 bg-brand-50 p-8 text-center text-brand-800 shadow-sm">
    <span class="text-xs font-semibold uppercase tracking-widest text-brand-700">Green Plant</span>
    <h1 class="mt-2 text-2xl font-bold text-brand-900">Taman impianmu dimulai di sini</h1>
    <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-brand-800/80">
        Tanaman pilihan siap dikirim ke rumahmu, plus layanan tukang taman profesional
        untuk membuat, merawat, dan menjaga tamanmu tetap hijau.
    </p>
    <div class="mt-5 flex flex-wrap justify-center gap-3">
        <a href="{{ route('produk.index') }}"
            class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Jelajahi Produk</a>
        <a href="{{ route('jasa.index') }}"
            class="rounded-lg border border-brand-700/40 px-5 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100">Lihat Jasa</a>
    </div>
</section>

{{-- Produk unggulan --}}
<section class="mt-8" aria-label="Produk terbaru">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Produk Terbaru</h2>
        <a href="{{ route('produk.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Lihat semua &rarr;</a>
    </div>
    @if ($produk->count())
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($produk as $p)
                <x-produk-card :produk="$p" :favorit-produk-ids="$favoritProdukIds" />
            @endforeach
        </div>
    @else
        <p class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Katalog produk belum tersedia.</p>
    @endif
</section>

{{-- Tip tanaman --}}
<aside class="relative mt-8 overflow-hidden rounded-xl border border-brand-100 bg-brand-50 px-5 py-4 sm:px-7" aria-label="Informasi tanaman">
    <div class="relative z-10 max-w-2xl pr-20 sm:pr-36">
        <p class="flex items-center gap-2 text-sm font-semibold text-brand-900">
            <svg class="h-5 w-5 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 4c-9 0-15 4-15 11a5 5 0 0 0 5 5c7 0 11-6 10-16Zm-14 14c3-4 7-7 12-10" />
            </svg>
            Tahukah kamu?
        </p>
        <p class="mt-1 text-sm leading-relaxed text-brand-800/80">
            Tanaman hijau dapat meningkatkan kualitas udara dan membuat suasana lebih segar.
        </p>
    </div>
    <svg class="pointer-events-none absolute -bottom-5 right-3 h-24 w-24 text-brand-600/80 sm:right-8 sm:h-32 sm:w-32"
        viewBox="0 0 120 120" fill="none" aria-hidden="true">
        <path d="M60 84V39M60 68C48 55 42 43 39 30M60 62C73 52 81 42 85 30" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        <path d="M39 31C25 20 25 9 30 4c15 3 19 14 9 27Zm46 0C90 14 101 8 110 10c0 15-9 23-25 21ZM60 42C46 29 46 17 52 9c15 8 17 20 9 33ZM59 72C44 59 31 59 23 64c8 14 21 17 36 8Zm2 0c15-14 28-14 36-8-7 14-21 18-36 8Z" fill="currentColor" />
        <path d="M40 83h40l-5 29H45l-5-29Z" fill="#b98f68" />
        <path d="M38 83h44" stroke="#8c6748" stroke-width="3" stroke-linecap="round" />
    </svg>
</aside>

{{-- Jasa unggulan --}}
<section class="mt-8" aria-label="Jasa terbaru">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Jasa Kami</h2>
        <a href="{{ route('jasa.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Lihat semua &rarr;</a>
    </div>
    @if ($jasa->count())
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($jasa as $j)
                <a href="{{ route('jasa.show', $j) }}"
                    class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="aspect-[4/3] overflow-hidden bg-gray-50">
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
    @else
        <p class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Layanan jasa belum tersedia.</p>
    @endif
</section>

{{-- Ajakan terakhir --}}
<section class="mt-8 rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm">
    <h2 class="text-base font-semibold">Butuh bantuan merawat taman?</h2>
    <p class="mx-auto mt-1 max-w-xl text-sm text-gray-600">
        Tim kami siap datang ke lokasimu sesuai jadwal &mdash; cukup pilih jasa, atur waktunya, dan selesai.
    </p>
    <x-button href="{{ route('jasa.index') }}" class="mt-4">Pesan Jasa Sekarang</x-button>
</section>
@endsection
