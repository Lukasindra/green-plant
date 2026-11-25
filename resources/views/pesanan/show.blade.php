@extends('layouts.app')

@section('judul', 'Pesanan ' . $pesanan->kode)

@section('isi')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <h1 class="text-xl font-semibold">Pesanan {{ $pesanan->kode }}</h1>
    <div class="flex items-center gap-3">
        <a href="{{ route('pesanan.invoice', $pesanan) }}" class="text-sm text-brand-700 hover:underline">Invoice</a>
        <a href="{{ route('pesanan.index') }}" class="text-sm text-brand-700 hover:underline">&larr; Semua pesanan</a>
    </div>
</div>

@if ($pesanan->status_pesanan !== 'dibatalkan')
    @php
        $progres = ['Dibuat', 'Lunas', 'Dikirim', 'Selesai'];
        $capai = [
            true,
            $pesanan->status_pembayaran === 'paid',
            in_array($pesanan->status_pesanan, ['dikirim', 'selesai'], true),
            $pesanan->status_pesanan === 'selesai',
        ];
    @endphp
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5" aria-label="Progres pesanan">
        <ol class="grid grid-cols-4 gap-2 text-center">
            @foreach ($progres as $i => $label)
                @php
                    $tercapai = $capai[$i];
                    $tengah = ! $tercapai && ($i === 0 || $capai[$i - 1]);
                @endphp
                <li class="flex flex-col items-center gap-1.5 text-xs">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full font-semibold
                        {{ $tercapai ? 'bg-brand-700 text-white' : ($tengah ? 'border-2 border-amber-500 bg-amber-50 text-amber-600' : 'border-2 border-gray-200 text-gray-400') }}">
                        {{ $tercapai ? '&#10003;' : $i + 1 }}
                    </span>
                    <span class="{{ $tercapai ? 'font-medium text-gray-800' : ($tengah ? 'font-medium text-amber-700' : 'text-gray-500') }}">{{ $label }}</span>
                </li>
            @endforeach
        </ol>
    </section>
@endif

<div class="grid gap-6 lg:grid-cols-2">

    {{-- Detail pesanan --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Detail Pesanan</h2>

        <dl class="mb-4 grid grid-cols-[110px_1fr] gap-y-1 text-sm">
            <dt class="text-gray-500">Tanggal</dt><dd>{{ $pesanan->tanggal_pesan->translatedFormat('d M Y') }}</dd>
            <dt class="text-gray-500">Pembayaran</dt><dd><x-status-badge :nilai="$pesanan->status_pembayaran" /></dd>
            <dt class="text-gray-500">Status</dt><dd><x-status-badge :nilai="$pesanan->status_pesanan" /></dd>
            <dt class="text-gray-500">Invoice</dt><dd>{{ $pesanan->invoice_number ?? $pesanan->kode }}</dd>
            <dt class="text-gray-500">Alamat</dt><dd>{{ $pesanan->alamat_pengiriman }}</dd>
        </dl>

        <ul class="divide-y divide-gray-100 border-t border-gray-200 text-sm">
            @foreach ($pesanan->detail as $d)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $d->produk?->nama }} <span class="text-gray-400">x{{ $d->qty }}</span></span>
                    <span>Rp {{ number_format($d->subtotal, 0, ',', '.') }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="mt-3 space-y-1 border-t border-gray-200 pt-3 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>Rp {{ number_format($pesanan->detail->sum('subtotal'), 0, ',', '.') }}</dd></div>
            <div class="flex justify-between"><dt>Ongkir</dt><dd>Rp {{ number_format($pesanan->ongkir, 0, ',', '.') }}</dd></div>
            <div class="flex justify-between pt-1 text-base font-semibold">
                <dt>Total Pembayaran</dt><dd class="text-brand-700">Rp {{ number_format($pesanan->total_bayar, 0, ',', '.') }}</dd>
            </div>
        </dl>
    </section>

    {{-- Pembayaran via Midtrans --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Pembayaran</h2>

        @if ($pesanan->status_pembayaran === 'paid')
            <p class="rounded-md bg-brand-50 px-4 py-3 text-sm text-brand-800">Pembayaran dikonfirmasi Midtrans. Terima kasih!</p>
            <p class="mt-2 text-xs text-gray-500">Metode pembayaran tercatat di jendela Midtrans (QRIS / e-wallet / transfer / kartu).</p>
        @elseif ($pesanan->status_pesanan === 'dibatalkan')
            <p class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">Pesanan ini dibatalkan.</p>
        @else
            <p class="mb-3 text-sm text-gray-600">
                Selesaikan pembayaran sebesar <strong>Rp {{ number_format($pesanan->total_bayar, 0, ',', '.') }}</strong> melalui jendela Midtrans (QRIS, e-wallet, transfer bank, atau kartu).
            </p>

            <a href="{{ route('payment.show', ['tipe' => 'produk', 'pesanan' => $pesanan->id]) }}"
               class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                {{ $pesanan->status_pembayaran === 'failed' ? 'Coba Bayar Ulang' : 'Bayar Sekarang' }}
            </a>

            @can('batalkan', $pesanan)
                <form method="POST" action="{{ route('pesanan.batalkan', $pesanan) }}" class="mt-5 border-t border-gray-200 pt-4"
                      onsubmit="return confirm('Batalkan pesanan {{ $pesanan->kode }}? Tindakan ini tidak dapat dibatalkan.')">
                    @csrf
                    <p class="mb-2 text-xs text-gray-500">Belum jadi membayar? Pesanan bisa dibatalkan selama pembayaran belum terkonfirmasi Midtrans.</p>
                    <x-button varian="danger" class="w-full">Batalkan Pesanan</x-button>
                </form>
            @endcan
        @endif
    </section>
</div>

@if ($pesanan->relationLoaded('riwayat') && $pesanan->riwayat->isNotEmpty())
    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-5" aria-label="Riwayat pesanan">
        <h2 class="mb-4 text-sm font-semibold">Riwayat Pesanan</h2>
        <ol class="space-y-3 border-l border-gray-200 pl-4">
            @foreach ($pesanan->riwayat->sortByDesc('created_at') as $r)
                <li class="relative">
                    <span class="absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full bg-brand-600"></span>
                    <p class="text-sm font-medium">{{ \App\Support\CatatanPesanan::label($r->status) }}</p>
                    @if ($r->keterangan)
                        <p class="text-xs text-gray-500">{{ $r->keterangan }}</p>
                    @endif
                    <p class="text-xs text-gray-400">{{ $r->created_at?->translatedFormat('d M Y H:i') }}</p>
                </li>
            @endforeach
        </ol>
    </section>
@endif
@endsection
