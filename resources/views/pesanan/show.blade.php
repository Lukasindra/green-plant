@extends('layouts.app')

@section('judul', 'Pesanan ' . $pesanan->kode)

@section('isi')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <h1 class="text-xl font-semibold">Pesanan {{ $pesanan->kode }}</h1>
    <a href="{{ route('pesanan.index') }}" class="text-sm text-brand-700 hover:underline">&larr; Semua pesanan</a>
</div>

<div class="grid gap-6 lg:grid-cols-2">

    {{-- Detail pesanan --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Detail Pesanan</h2>

        <dl class="mb-4 grid grid-cols-[110px_1fr] gap-y-1 text-sm">
            <dt class="text-gray-500">Tanggal</dt><dd>{{ $pesanan->tanggal_pesan->format('d M Y') }}</dd>
            <dt class="text-gray-500">Pembayaran</dt><dd><x-status-badge :nilai="$pesanan->status_pembayaran" /></dd>
            <dt class="text-gray-500">Status</dt><dd><x-status-badge :nilai="$pesanan->status_pesanan" /></dd>
            <dt class="text-gray-500">Metode</dt><dd>{{ config('toko.metode.' . $pesanan->metode_pembayaran, $pesanan->metode_pembayaran) }}</dd>
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

    {{-- Pembayaran --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Pembayaran</h2>

        @if ($pesanan->status_pembayaran === 'lunas')
            <p class="rounded-md bg-brand-50 px-4 py-3 text-sm text-brand-800">Pembayaran sudah dikonfirmasi. Terima kasih!</p>
        @elseif ($pesanan->status_pesanan === 'dibatalkan')
            <p class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">Pesanan ini dibatalkan.</p>
        @else
            @switch($pesanan->metode_pembayaran)
                @case('transfer')
                    <p class="mb-2 text-sm">Transfer sebesar <strong>Rp {{ number_format($pesanan->total_bayar, 0, ',', '.') }}</strong> ke salah satu rekening:</p>
                    <ul class="space-y-1 text-sm">
                        @foreach (config('toko.rekening') as $r)
                            <li class="rounded-md bg-gray-50 px-3 py-2"><strong>{{ $r['bank'] }}</strong> {{ $r['nomor'] }} a.n. {{ $r['nama'] }}</li>
                        @endforeach
                    </ul>
                    @break

                @case('ewallet')
                    <p class="mb-2 text-sm">Kirim <strong>Rp {{ number_format($pesanan->total_bayar, 0, ',', '.') }}</strong> ke salah satu e-wallet:</p>
                    <ul class="space-y-1 text-sm">
                        @foreach (config('toko.ewallet') as $e)
                            <li class="rounded-md bg-gray-50 px-3 py-2"><strong>{{ $e['nama'] }}</strong> {{ $e['nomor'] }}</li>
                        @endforeach
                    </ul>
                    @break

                @case('qris')
                    <p class="mb-2 text-sm">Scan kode QRIS dan bayar <strong>Rp {{ number_format($pesanan->total_bayar, 0, ',', '.') }}</strong>.</p>
                    @if (file_exists(public_path(config('toko.qris_gambar'))))
                        <img src="{{ asset(config('toko.qris_gambar')) }}" alt="QRIS Green Plant" class="mx-auto h-48 w-48 rounded-md border border-gray-200 object-contain">
                    @else
                        <p class="rounded-md bg-gray-50 px-3 py-6 text-center text-sm text-gray-500">Kode QRIS belum dipasang.</p>
                    @endif
                    @break

                @case('cod')
                    <p class="rounded-md bg-gray-50 px-4 py-3 text-sm">Bayar tunai saat pesanan diterima. Admin akan menghubungi Anda untuk konfirmasi.</p>
                    @break
            @endswitch

            @can('uploadBukti', $pesanan)
                <form method="POST" action="{{ route('pesanan.bukti.simpan', $pesanan) }}" enctype="multipart/form-data" class="mt-5 border-t border-gray-200 pt-4">
                    @csrf
                    <label for="bukti_bayar" class="mb-1 block text-sm font-medium">Unggah bukti pembayaran</label>
                    <input id="bukti_bayar" type="file" name="bukti_bayar" accept="image/png,image/jpeg" required
                           class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-800 hover:file:bg-brand-200">
                    <p class="mt-1 text-xs text-gray-500">JPG atau PNG, maksimal 2 MB.</p>
                    @error('bukti_bayar') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                    <button type="submit" class="mt-3 w-full rounded-lg bg-brand-700 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Kirim Bukti</button>
                </form>
            @endcan

            @if ($pesanan->bukti_bayar)
                <p class="mt-3 text-sm">
                    <a href="{{ route('pesanan.bukti', $pesanan) }}" target="_blank" class="font-medium text-brand-700 hover:underline">Lihat bukti yang diunggah</a>
                </p>
            @endif
        @endif
    </section>
</div>
@endsection
