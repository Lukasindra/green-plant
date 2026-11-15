@extends('layouts.admin')

@section('judul', 'Pesanan '.$pesanan->kode)

@section('isi')
    @php
        $ruteAksi = $tipe === 'jasa'
            ? route('admin.pesanan.jasa.aksi', $pesanan)
            : route('admin.pesanan.produk.aksi', $pesanan);

        $punyaStatusPesanan = isset($pesanan->status_pesanan);
        $statusPesanan = $punyaStatusPesanan ? ($pesanan->status_pesanan ?? 'diproses') : 'diproses';
        $lunas = in_array($pesanan->status_pembayaran, ['lunas', 'dibayar'], true);
        $dibatalkan = $punyaStatusPesanan && $statusPesanan === 'dibatalkan';
    @endphp

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(20rem,0.95fr)]">
        {{-- Detail pesanan --}}
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Detail Pesanan</h2>
                <a href="{{ route('admin.pesanan.index') }}" class="text-sm text-brand-800 hover:text-brand-900">&larr; Semua pesanan</a>
            </div>

            <dl class="grid grid-cols-[130px_1fr] gap-y-1.5 text-sm">
                <dt class="text-gray-500">Kode</dt><dd class="font-medium">{{ $pesanan->kode }}</dd>
                <dt class="text-gray-500">Pelanggan</dt><dd>{{ $pesanan->customer?->akun?->nama ?? '-' }}</dd>
                <dt class="text-gray-500">Tanggal</dt><dd>{{ $pesanan->tanggal_pesan?->format('d M Y') }}</dd>
                <dt class="text-gray-500">Metode</dt><dd>{{ config('toko.metode.'.$pesanan->metode_pembayaran, $pesanan->metode_pembayaran) }}</dd>
                <dt class="text-gray-500">Pembayaran</dt><dd><x-status-badge :nilai="$pesanan->status_pembayaran" /></dd>
                <dt class="text-gray-500">Status</dt>
                <dd>
                    @if ($punyaStatusPesanan)
                        <x-status-badge :nilai="$pesanan->status_pesanan" />
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </dd>

                @if ($tipe === 'produk')
                    <dt class="text-gray-500">Alamat kirim</dt><dd class="whitespace-pre-line">{{ $pesanan->alamat_pengiriman }}</dd>
                @else
                    <dt class="text-gray-500">Lokasi kerja</dt><dd class="whitespace-pre-line">{{ $pesanan->alamat_lokasi }}</dd>
                    @if ($pesanan->catatan)
                        <dt class="text-gray-500">Catatan</dt><dd class="whitespace-pre-line">{{ $pesanan->catatan }}</dd>
                    @endif
                    @if ($pesanan->jadwal_pengerjaan)
                        <dt class="text-gray-500">Jadwal</dt><dd>{{ $pesanan->jadwal_pengerjaan->format('d M Y H:i') }}</dd>
                    @endif
                @endif
            </dl>

            <ul class="mt-4 divide-y divide-gray-100 border-t border-gray-200 text-sm">
                @foreach ($pesanan->detail as $d)
                    <li class="flex items-center justify-between py-2">
                        <span>
                            {{ $tipe === 'jasa' ? $d->jasa?->nama : $d->produk?->nama }} <span class="text-gray-400">x{{ $d->qty }}</span>
                        </span>
                        <span>Rp {{ number_format($d->subtotal, 0, ',', '.') }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="mt-3 space-y-1 border-t border-gray-200 pt-3 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>Rp {{ number_format($pesanan->detail->sum('subtotal'), 0, ',', '.') }}</dd></div>
                @if ($tipe === 'produk')
                    <div class="flex justify-between"><dt>Ongkir</dt><dd>Rp {{ number_format($pesanan->ongkir, 0, ',', '.') }}</dd></div>
                @endif
                <div class="flex justify-between pt-1 text-base font-semibold">
                    <dt>Total Pembayaran</dt><dd class="text-brand-700">Rp {{ number_format($pesanan->total_bayar, 0, ',', '.') }}</dd>
                </div>
            </dl>
        </section>

        {{-- Pembayaran & aksi --}}
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 font-semibold text-gray-900">Pembayaran</h2>

            @if ($pesanan->bukti_bayar)
                <div class="mb-4 overflow-hidden rounded-lg border border-gray-200">
                    <img src="{{ route('admin.pesanan.bukti', ['tipe' => $tipe, 'id' => $pesanan->id]) }}"
                         alt="Bukti pembayaran {{ $pesanan->kode }}"
                         class="h-64 w-full bg-gray-50 object-contain">
                </div>
                <p class="mb-4 text-xs text-gray-500">Sumber: {{ $pesanan->bukti_bayar }}</p>
            @else
                <p class="mb-4 rounded-md bg-gray-50 px-3 py-3 text-sm text-gray-500">Belum ada bukti pembayaran yang diunggah.</p>
            @endif

            @unless ($dibatalkan)
                <div class="flex flex-col gap-2 border-t border-gray-100 pt-4">
                    @if ($pesanan->status_pembayaran === 'menunggu_verifikasi')
                        <form method="POST" action="{{ $ruteAksi }}">
                            @csrf
                            <input type="hidden" name="dari" value="detail">
                            <input type="hidden" name="aksi" value="verifikasi_bayar">
                            <button type="submit" class="w-full rounded-lg bg-brand-700 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Verifikasi Bukti Bayar</button>
                        </form>
                        <form method="POST" action="{{ $ruteAksi }}">
                            @csrf
                            <input type="hidden" name="dari" value="detail">
                            <input type="hidden" name="aksi" value="tolak_bayar">
                            <button type="submit" class="w-full rounded-lg border border-gray-300 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Tolak Bukti Bayar</button>
                        </form>
                    @endif

                    @if ($pesanan->metode_pembayaran === 'cod' && $pesanan->status_pembayaran === 'menunggu_bayar')
                        <form method="POST" action="{{ $ruteAksi }}">
                            @csrf
                            <input type="hidden" name="dari" value="detail">
                            <input type="hidden" name="aksi" value="konfirmasi_cod">
                            <button type="submit" class="w-full rounded-lg bg-brand-700 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Konfirmasi COD</button>
                        </form>
                    @endif

                    @if ($tipe === 'produk' && $lunas && ! in_array($statusPesanan, ['dikirim', 'selesai'], true))
                        <form method="POST" action="{{ $ruteAksi }}">
                            @csrf
                            <input type="hidden" name="dari" value="detail">
                            <input type="hidden" name="aksi" value="dikirim">
                            <button type="submit" class="w-full rounded-lg bg-brand-700 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Tandai Dikirim</button>
                        </form>
                    @endif

                    @if ($lunas && ! in_array($statusPesanan, ['selesai'], true) && ($tipe === 'jasa' || $statusPesanan === 'dikirim' || ! $punyaStatusPesanan))
                        <form method="POST" action="{{ $ruteAksi }}">
                            @csrf
                            <input type="hidden" name="dari" value="detail">
                            <input type="hidden" name="aksi" value="selesai">
                            <button type="submit" class="w-full rounded-lg bg-brand-700 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Tandai Selesai</button>
                        </form>
                    @endif

                    @if (! $lunas && $pesanan->status_pembayaran !== 'menunggu_verifikasi' && ! in_array($statusPesanan, ['dikirim', 'selesai'], true))
                        <form method="POST" action="{{ $ruteAksi }}" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?')">
                            @csrf
                            <input type="hidden" name="dari" value="detail">
                            <input type="hidden" name="aksi" value="batalkan">
                            <button type="submit" class="w-full rounded-lg border border-red-200 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Batalkan Pesanan</button>
                        </form>
                    @endif
                </div>
            @else
                <p class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">Pesanan ini sudah dibatalkan.</p>
            @endunless
        </section>
    </div>
@endsection
