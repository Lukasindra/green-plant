@extends('layouts.app')

@section('judul', 'Antar ' . $pesanan->kode)

@section('isi')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-semibold">Pesanan {{ $pesanan->kode }}</h1>
        <a href="{{ route('kurir.index') }}" class="text-sm text-brand-700 hover:underline">&larr; Tugas antar</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold">Tujuan Pengantaran</h2>
            <dl class="grid grid-cols-[110px_1fr] gap-y-1 text-sm">
                <dt class="text-gray-500">Penerima</dt><dd>{{ $pesanan->customer?->akun?->nama ?? '-' }}</dd>
                <dt class="text-gray-500">No. HP</dt><dd>{{ $pesanan->customer?->akun?->no_hp ?: '-' }}</dd>
                <dt class="text-gray-500">Alamat</dt><dd>{{ $pesanan->alamat_pengiriman }}</dd>
                <dt class="text-gray-500">Status</dt><dd><x-status-badge :nilai="$pesanan->status_pesanan" /></dd>
            </dl>

            @if ($pesanan->metode_pembayaran === 'cod')
                <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    Pesanan COD. Pembayaran dikonfirmasi admin.
                </p>
            @endif
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold">Isi Pesanan</h2>
            <ul class="divide-y divide-gray-100 border-t border-gray-200 text-sm">
                @foreach ($pesanan->detail as $d)
                    <li class="flex items-center justify-between py-2">
                        <span>{{ $d->produk?->nama ?? 'Produk' }}</span>
                        <span class="text-gray-500">x{{ $d->qty }}</span>
                    </li>
                @endforeach
            </ul>

            @if ($pesanan->status_pesanan === 'dikirim')
                <form method="POST" action="{{ route('kurir.selesai', $pesanan) }}" class="mt-5"
                      onsubmit="return confirm('Tandai pesanan ini sudah diterima customer?')">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
                        Tandai Terkirim
                    </button>
                </form>
            @endif
        </section>
    </div>
@endsection
