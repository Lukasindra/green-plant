@extends('layouts.app')

@section('judul', 'Tentang Kami')

@section('isi')
{{-- Hero --}}
<section class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
    <span class="text-xs font-semibold uppercase tracking-widest text-teal-600">Green Plant</span>
    <h1 class="mt-2 text-2xl font-bold text-gray-900">Solusi hijau untuk taman impian Anda</h1>
    <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-gray-600">
        Green Plant menyediakan tanaman pilihan dan jasa taman profesional untuk rumah, kantor,
        maupun ruang komersial. Kami percaya setiap ruang bisa lebih hidup &mdash; dan kami bantu
        mewujudkannya dari pemilihan tanaman, penanaman, hingga perawatan berkala.
    </p>
    <div class="mt-5 flex flex-wrap justify-center gap-3">
        <x-button href="{{ route('produk.index') }}">Jelajahi Produk</x-button>
        <x-button varian="outline" href="{{ route('jasa.index') }}">Lihat Jasa Kami</x-button>
    </div>
</section>

{{-- Keunggulan --}}
<section class="mt-6" aria-label="Keunggulan kami">
    <div class="grid gap-4 sm:grid-cols-3">
        <article class="rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <p class="text-2xl text-brand-700" aria-hidden="true">&#10048;</p>
            <h2 class="mt-2 text-sm font-semibold">Tanaman Terpilih</h2>
            <p class="mt-2 text-xs leading-relaxed text-gray-600">
                Koleksi tanaman dikurasi dan dikirim dalam kondisi sehat, siap tumbuh di ruang Anda.
            </p>
        </article>
        <article class="rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <p class="text-2xl text-brand-700" aria-hidden="true">&#128295;</p>
            <h2 class="mt-2 text-sm font-semibold">Layanan Profesional</h2>
            <p class="mt-2 text-xs leading-relaxed text-gray-600">
                Tim tukang taman berpengalaman menangani pembuatan dan perawatan taman sesuai jadwal.
            </p>
        </article>
        <article class="rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <p class="text-2xl text-brand-700" aria-hidden="true">&#128176;</p>
            <h2 class="mt-2 text-sm font-semibold">Transparan &amp; Mudah</h2>
            <p class="mt-2 text-xs leading-relaxed text-gray-600">
                Harga jelas, pesanan terlacak, dan konfirmasi pembayaran manual oleh tim kami.
            </p>
        </article>
    </div>
</section>

{{-- Cara kerja --}}
<section class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm" aria-label="Cara memesan">
    <h2 class="text-sm font-semibold">Cara Memesan</h2>
    <ol class="mt-4 grid gap-4 text-sm text-gray-600 sm:grid-cols-4">
        <li><span class="font-semibold text-brand-700">1.</span> Pilih produk atau jasa, lalu masukkan ke keranjang.</li>
        <li><span class="font-semibold text-brand-700">2.</span> Isi alamat dan pilih metode pembayaran di halaman checkout.</li>
        <li><span class="font-semibold text-brand-700">3.</span> Unggah bukti pembayaran di halaman pesanan Anda.</li>
        <li><span class="font-semibold text-brand-700">4.</span> Tim kami verifikasi, lalu pesanan dikirim atau dikerjakan.</li>
    </ol>
</section>
@endsection
