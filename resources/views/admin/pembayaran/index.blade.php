@extends('layouts.admin')

@section('judul', 'Pembayaran')

@section('isi')
    @php
        $daftarJenis = [
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'menunggu_bayar' => 'Menunggu Bayar',
            'lunas' => 'Lunas',
        ];

        $daftarTipe = [
            'semua' => 'Semua',
            'produk' => 'Produk',
            'jasa' => 'Jasa',
        ];
    @endphp

    {{-- Ringkasan --}}
    <section aria-label="Ringkasan pembayaran" class="grid gap-4 sm:grid-cols-3">
        @foreach ($ringkasan as $kunciJenis => $ringkas)
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">{{ $daftarJenis[$kunciJenis] }}</p>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($ringkas['jumlah']) }}</p>
                <p class="mt-1 text-xs text-gray-500">Nilai: Rp {{ number_format($ringkas['nilai'], 0, ',', '.') }}</p>
            </article>
        @endforeach
    </section>

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($daftarJenis as $kunciJenis => $labelJenis)
                <a href="{{ route('admin.pembayaran.index', array_filter(['tipe' => $tipe, 'jenis' => $kunciJenis])) }}"
                   @if ($jenis === $kunciJenis) aria-current="page" @endif
                   class="rounded-full px-4 py-1.5 text-sm font-medium {{ $jenis === $kunciJenis ? 'bg-brand-700 text-white' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                    {{ $labelJenis }}
                </a>
            @endforeach

            <div class="ml-auto flex flex-wrap items-center gap-2">
                @foreach ($daftarTipe as $tipeOpsi => $labelTipe)
                    <a href="{{ route('admin.pembayaran.index', array_filter(['tipe' => $tipeOpsi, 'jenis' => $jenis])) }}"
                       @if ($tipe === $tipeOpsi) aria-current="page" @endif
                       class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $tipe === $tipeOpsi ? 'border-brand-700 bg-brand-50 text-brand-800' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                        {{ $labelTipe }}
                    </a>
                @endforeach
            </div>
        </div>

        <section aria-label="Daftar pembayaran" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @if ($pesanan->isEmpty())
                <p class="px-6 py-14 text-center text-sm text-gray-500">Tidak ada pembayaran pada status ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[52rem] text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-medium">Kode / Pelanggan</th>
                                <th scope="col" class="px-3 py-3 font-medium">Jenis</th>
                                <th scope="col" class="px-3 py-3 font-medium">Metode</th>
                                <th scope="col" class="px-3 py-3 text-right font-medium">Nilai</th>
                                <th scope="col" class="px-3 py-3 font-medium">Bukti</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($pesanan as $item)
                                @php
                                    $adalahJasa = $item instanceof \App\Models\PesananJasa;
                                    $tipeParam = $adalahJasa ? 'jasa' : 'produk';
                                    $ruteDetail = $adalahJasa ? 'admin.pesanan.jasa.show' : 'admin.pesanan.produk.show';
                                    $ruteAksi = $adalahJasa ? route('admin.pesanan.jasa.aksi', $item) : route('admin.pesanan.produk.aksi', $item);
                                @endphp
                                <tr>
                                    <td class="px-5 py-3.5">
                                        <span class="block font-medium text-gray-900">{{ $item->kode }}</span>
                                        <span class="mt-1 block text-xs text-gray-500">{{ $item->customer?->akun?->nama ?? '-' }}</span>
                                    </td>
                                    <td class="px-3 py-3.5 text-gray-600">{{ $adalahJasa ? 'Jasa' : 'Produk' }}</td>
                                    <td class="px-3 py-3.5 text-gray-600">{{ config('toko.metode.'.$item->metode_pembayaran, $item->metode_pembayaran) }}</td>
                                    <td class="whitespace-nowrap px-3 py-3.5 text-right font-medium text-gray-800">Rp {{ number_format($item->total_bayar, 0, ',', '.') }}</td>
                                    <td class="px-3 py-3.5">
                                        @if ($item->bukti_bayar)
                                            <a href="{{ route($ruteDetail, $item) }}" title="Lihat detail & bukti bayar">
                                                <img src="{{ route('admin.pesanan.bukti', ['tipe' => $tipeParam, 'id' => $item->id]) }}"
                                                     alt="Bukti bayar {{ $item->kode }}"
                                                     class="h-12 w-20 rounded border border-gray-200 object-cover">
                                            </a>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center justify-end gap-3">
                                            @if ($item->status_pembayaran === 'menunggu_verifikasi')
                                                <form method="POST" action="{{ $ruteAksi }}">
                                                    @csrf
                                                    <input type="hidden" name="dari" value="pembayaran">
                                                    <input type="hidden" name="aksi" value="verifikasi_bayar">
                                                    <button type="submit" class="font-medium text-brand-800 hover:text-brand-900">Verifikasi</button>
                                                </form>
                                                <form method="POST" action="{{ $ruteAksi }}">
                                                    @csrf
                                                    <input type="hidden" name="dari" value="pembayaran">
                                                    <input type="hidden" name="aksi" value="tolak_bayar">
                                                    <button type="submit" class="font-medium text-gray-600 hover:text-red-700">Tolak</button>
                                                </form>
                                            @endif

                                            @if ($item->metode_pembayaran === 'cod' && $item->status_pembayaran === 'menunggu_bayar')
                                                <form method="POST" action="{{ $ruteAksi }}">
                                                    @csrf
                                                    <input type="hidden" name="dari" value="pembayaran">
                                                    <input type="hidden" name="aksi" value="konfirmasi_cod">
                                                    <button type="submit" class="font-medium text-brand-800 hover:text-brand-900">Konfirmasi COD</button>
                                                </form>
                                            @endif

                                            <a href="{{ route($ruteDetail, $item) }}" class="font-medium text-brand-800 hover:text-brand-900">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $pesanan->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
