@extends('layouts.app')

@section('judul', 'Pesanan ' . $pesanan->kode)

@section('isi')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <h1 class="text-xl font-semibold">Pesanan {{ $pesanan->kode }}</h1>
    <a href="{{ route('pesanan-jasa.index') }}" class="text-sm text-brand-700 hover:underline">&larr; Semua pesanan jasa</a>
</div>

@if ($pesanan->status_pesanan !== 'dibatalkan')
    @php
        $progres = ['Dibuat', 'Lunas', 'Dikerjakan', 'Selesai'];
        $capai = [
            true,
            $pesanan->status_pembayaran === 'paid',
            in_array($pesanan->status_pesanan, ['dikerjakan', 'selesai'], true),
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
            <dt class="text-gray-500">Tanggal</dt><dd>{{ $pesanan->tanggal_pesan->format('d M Y') }}</dd>
            <dt class="text-gray-500">Jadwal</dt><dd>{{ $pesanan->jadwal_pengerjaan?->format('d M Y, H:i') ?? '-' }}</dd>
            <dt class="text-gray-500">Pembayaran</dt><dd><x-status-badge :nilai="$pesanan->status_pembayaran" /></dd>
            <dt class="text-gray-500">Status</dt><dd><x-status-badge :nilai="$pesanan->status_pesanan" /></dd>
            <dt class="text-gray-500">Invoice</dt><dd>{{ $pesanan->invoice_number ?? $pesanan->kode }}</dd>
            <dt class="text-gray-500">Lokasi</dt><dd>{{ $pesanan->alamat_lokasi }}</dd>
            <dt class="text-gray-500">Catatan</dt><dd>{{ $pesanan->catatan ?: '-' }}</dd>
        </dl>

        <ul class="divide-y divide-gray-100 border-t border-gray-200 text-sm">
            @foreach ($pesanan->detail as $d)
                <li class="flex items-center justify-between py-2">
                    <span><span class="font-medium text-teal-600">{{ $d->jasa?->nama }}</span> <span class="text-gray-400">x{{ $d->qty }} {{ $d->jasa?->satuan }}</span></span>
                    <span>Rp {{ number_format($d->subtotal, 0, ',', '.') }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="mt-3 space-y-1 border-t border-gray-200 pt-3 text-sm">
            <div class="flex justify-between"><dt>Total Pembayaran</dt>
                <dd class="text-base font-semibold text-brand-700">Rp {{ number_format($pesanan->total_bayar, 0, ',', '.') }}</dd>
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

            <a href="{{ route('payment.show', ['tipe' => 'jasa', 'pesanan' => $pesanan->id]) }}"
               class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                {{ $pesanan->status_pembayaran === 'failed' ? 'Coba Bayar Ulang' : 'Bayar Sekarang' }}
            </a>

            @can('batalkan', $pesanan)
                <form method="POST" action="{{ route('pesanan-jasa.batalkan', $pesanan) }}" class="mt-5 border-t border-gray-200 pt-4"
                      onsubmit="return confirm('Batalkan pesanan {{ $pesanan->kode }}? Tindakan ini tidak dapat dibatalkan.')">
                    @csrf
                    <p class="mb-2 text-xs text-gray-500">Belum jadi membayar? Pesanan bisa dibatalkan selama pembayaran belum terkonfirmasi Midtrans.</p>
                    <x-button varian="danger" class="w-full">Batalkan Pesanan</x-button>
                </form>
            @endcan
        @endif
    </section>
</div>
@endsection
