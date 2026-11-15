@extends('layouts.app')

<<<<<<< HEAD
@section('judul', 'Keranjang Belanja')

@section('isi')
    <div class="bg-gradient-to-r from-brand-50 via-white to-brand-100">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8">
            <h1 class="text-2xl font-bold text-brand-900 sm:text-3xl">Keranjang Belanja</h1>
            <p class="text-sm text-gray-600">Periksa kembali tanaman pilihan Anda sebelum melanjutkan.</p>
        </div>
    </div>

    <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section aria-labelledby="cart-items-heading" class="min-w-0">
            <h2 id="cart-items-heading" class="sr-only">Produk di keranjang</h2>

            @if ($cartItems->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                    <p class="text-lg font-semibold text-gray-800">Keranjang Anda masih kosong</p>
                    <p class="mt-2 text-sm text-gray-500">Temukan tanaman yang cocok untuk rumah Anda.</p>
                    <a href="{{ route('produk.index') }}" class="mt-6 inline-flex rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
                        Lanjut Belanja
                    </a>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="hidden grid-cols-[minmax(0,1fr)_8rem_9rem_8rem] gap-4 border-b border-gray-200 bg-gray-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 md:grid">
                        <span>Produk</span>
                        <span class="text-right">Harga</span>
                        <span class="text-center">Jumlah</span>
                        <span class="text-right">Subtotal</span>
                    </div>

                    <ul class="divide-y divide-gray-100">
                        @foreach ($cartItems as $item)
                            @php
                                $produk = $item['produk'];
                            @endphp
                            <li class="grid gap-4 p-4 sm:p-5 md:grid-cols-[minmax(0,1fr)_8rem_9rem_8rem] md:items-center">
                                <div class="flex min-w-0 items-center gap-4">
                                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-gray-50 sm:h-24 sm:w-24">
                                        <x-gambar-produk :foto="$produk->foto" :nama="$produk->nama" />
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="truncate font-semibold text-gray-900">{{ $produk->nama }}</h3>
                                        <p class="mt-1 text-sm text-gray-500">{{ $produk->kategori?->nama ?? 'Tanaman' }}</p>
                                        <p class="mt-2 text-sm font-medium text-brand-800 md:hidden">
                                            Rp {{ number_format($produk->harga, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>

                                <p class="hidden text-right text-sm font-medium text-gray-700 md:block">
                                    Rp {{ number_format($produk->harga, 0, ',', '.') }}
                                </p>

                                <div class="flex items-center justify-between gap-3 md:justify-center">
                                    <span class="text-sm text-gray-500 md:hidden">Jumlah</span>
                                    <form method="POST" action="{{ route('keranjang.update', $produk) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="jumlah-{{ $produk->id }}">Jumlah {{ $produk->nama }}</label>
                                        <input
                                            id="jumlah-{{ $produk->id }}"
                                            type="number"
                                            name="jumlah"
                                            min="1"
                                            max="{{ $produk->stok }}"
                                            value="{{ $item['jumlah'] }}"
                                            class="w-20 rounded-md border border-gray-300 px-2 py-1.5 text-center text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600"
                                            onchange="this.form.requestSubmit()"
                                        >
                                        <button type="submit" class="sr-only">Perbarui jumlah</button>
                                    </form>
                                </div>

                                <div class="flex items-center justify-between gap-3 md:justify-end">
                                    <span class="text-sm text-gray-500 md:hidden">Subtotal</span>
                                    <span class="text-sm font-semibold text-brand-800">
                                        Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                    </span>
                                </div>

                                <div class="flex justify-end md:col-span-4">
                                    <form method="POST" action="{{ route('keranjang.destroy', $produk) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-700 hover:text-red-800">
                                            Hapus produk
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-4 sm:px-5">
                        <a href="{{ route('produk.index') }}" class="text-sm font-semibold text-brand-800 hover:text-brand-900">
                            &larr; Lanjut Belanja
                        </a>
                        <form method="POST" action="{{ route('keranjang.clear') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-gray-600 hover:text-red-700">
                                Hapus Semua
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </section>

        <aside aria-labelledby="cart-summary-heading" class="h-fit rounded-xl border border-gray-200 bg-white p-5">
            <h2 id="cart-summary-heading" class="text-lg font-semibold text-gray-900">Ringkasan Keranjang</h2>
            <dl class="mt-5 flex flex-col gap-4 text-sm">
                <div class="flex items-center justify-between gap-4 text-gray-600">
                    <dt>Jumlah produk</dt>
                    <dd>{{ $jumlahProduk }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-4 font-semibold text-gray-900">
                    <dt>Subtotal</dt>
                    <dd>Rp {{ number_format($subtotal, 0, ',', '.') }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs leading-5 text-gray-500">
                Ongkos kirim dan pembayaran akan tersedia setelah fitur checkout dibuat.
            </p>
            <button type="button" disabled class="mt-5 w-full cursor-not-allowed rounded-lg bg-gray-200 px-4 py-3 text-sm font-semibold text-gray-500">
                Checkout segera hadir
            </button>
        </aside>
    </div>
=======
@section('judul', 'Keranjang')

@section('isi')
<h1 class="mb-4 text-xl font-semibold">Keranjang Belanja</h1>

@if (count($baris))
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        {{-- Judul kolom (layar lebar) --}}
        <div class="hidden grid-cols-[1fr_110px_140px_120px_50px] gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-medium text-gray-500 md:grid">
            <span>Produk</span><span>Harga</span><span>Jumlah</span><span>Subtotal</span><span>Aksi</span>
        </div>

        @foreach ($baris as $b)
            @php $p = $b['produk']; @endphp
            <div class="grid grid-cols-2 items-center gap-3 border-b border-gray-100 px-4 py-4 last:border-b-0 md:grid-cols-[1fr_110px_140px_120px_50px]">

                {{-- Produk --}}
                <div class="col-span-2 flex items-center gap-3 md:col-span-1">
                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-gray-50">
                        <x-gambar-produk :foto="$p->foto" :nama="$p->nama" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold">{{ $p->nama }}</p>
                        <p class="text-xs text-gray-500">{{ $p->kategori?->nama }}</p>
                    </div>
                </div>

                {{-- Harga --}}
                <p class="text-sm">Rp {{ number_format($p->harga, 0, ',', '.') }}</p>

                {{-- Jumlah --}}
                <div class="flex items-center gap-1">
                    <form method="POST" action="{{ route('keranjang.ubah') }}">
                        @csrf
                        <input type="hidden" name="produk_id" value="{{ $p->id }}">
                        <input type="hidden" name="aksi" value="kurang">
                        <button type="submit" aria-label="Kurangi" class="h-7 w-7 rounded border border-gray-300 text-sm hover:bg-gray-100">&minus;</button>
                    </form>
                    <span class="w-8 text-center text-sm">{{ $b['qty'] }}</span>
                    <form method="POST" action="{{ route('keranjang.ubah') }}">
                        @csrf
                        <input type="hidden" name="produk_id" value="{{ $p->id }}">
                        <input type="hidden" name="aksi" value="tambah">
                        <button type="submit" aria-label="Tambah" class="h-7 w-7 rounded border border-gray-300 text-sm hover:bg-gray-100">+</button>
                    </form>
                </div>

                {{-- Subtotal --}}
                <p class="text-sm font-medium">Rp {{ number_format($b['subtotal'], 0, ',', '.') }}</p>

                {{-- Hapus --}}
                <form method="POST" action="{{ route('keranjang.hapus', $p) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" aria-label="Hapus {{ $p->nama }}" class="text-gray-400 hover:text-red-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3"/></svg>
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <div class="mt-4 flex items-center justify-end gap-6">
        <span class="text-sm text-gray-600">Total Belanja</span>
        <span class="text-lg font-semibold text-brand-700">Rp {{ number_format($total, 0, ',', '.') }}</span>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
        <a href="{{ route('produk.index') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-medium hover:bg-gray-50">
            &larr; Lanjut Belanja
        </a>

        @auth
            <a href="{{ route('checkout.index') }}" class="rounded-lg bg-brand-700 px-8 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-800">Checkout</a>
        @else
            <a href="{{ route('login') }}" class="rounded-lg bg-brand-700 px-8 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-800">Login untuk Checkout</a>
        @endauth
    </div>
@else
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
        <p class="text-sm text-gray-500">Keranjang Anda masih kosong.</p>
        <a href="{{ route('produk.index') }}" class="mt-4 inline-block rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Mulai Belanja</a>
    </div>
@endif
>>>>>>> 4561d79cb7f3dba4f34a1bbf2a7638dff7d6e969
@endsection
