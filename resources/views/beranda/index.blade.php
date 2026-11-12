@extends('layouts.app')

@section('judul', 'Beranda')

@section('isi')
{{-- Hero --}}
<section class="rounded-xl border border-brand-200 bg-gradient-to-r from-brand-800 to-brand-700 p-8 text-center text-white shadow-sm">
    <span class="text-xs font-semibold uppercase tracking-widest text-brand-100/80">Green Plant</span>
    <h1 class="mt-2 text-2xl font-bold">Taman impianmu dimulai di sini</h1>
    <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-brand-100/90">
        Tanaman pilihan siap dikirim ke rumahmu, plus layanan tukang taman profesional
        untuk membuat, merawat, dan menjaga tamanmu tetap hijau.
    </p>
    <div class="mt-5 flex flex-wrap justify-center gap-3">
        <a href="{{ route('produk.index') }}"
            class="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-50">Jelajahi Produk</a>
        <a href="{{ route('jasa.index') }}"
            class="rounded-lg border border-white/40 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">Lihat Jasa</a>
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
                <x-produk-card :produk="$p" />
            @endforeach
        </div>
    @else
        <p class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Katalog produk belum tersedia.</p>
    @endif
</section>

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
