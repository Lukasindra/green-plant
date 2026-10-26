@extends('layouts.admin')

@section('judul', 'Laporan')

@section('isi')
    @php
        $formatUang = fn ($nilai) => 'Rp ' . number_format((float) $nilai, 0, ',', '.');
    @endphp

    <div class="space-y-6">
        {{-- Filter periode --}}
        <form method="GET" action="{{ route('admin.laporan.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div>
                <label for="awal" class="mb-1 block text-sm font-medium text-gray-700">Dari tanggal</label>
                <input type="date" id="awal" name="awal" value="{{ $awal }}"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
            </div>
            <div>
                <label for="akhir" class="mb-1 block text-sm font-medium text-gray-700">Sampai tanggal</label>
                <input type="date" id="akhir" name="akhir" value="{{ $akhir }}"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
            </div>
            <button type="submit"
                class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                Terapkan
            </button>
        </form>

        {{-- Ringkasan --}}
        <section aria-label="Ringkasan laporan" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total Pesanan</p>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ $total->jumlah_produk + $total->jumlah_jasa }}</p>
                <p class="mt-1 text-xs text-gray-500">Produk {{ $total->jumlah_produk }} · Jasa {{ $total->jumlah_jasa }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total Nilai</p>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ $formatUang($total->total_nilai) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Nilai Produk</p>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ $formatUang($total->nilai_produk) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Nilai Jasa</p>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ $formatUang($total->nilai_jasa) }}</p>
            </article>
        </section>

        {{-- Rekap per tanggal --}}
        <section aria-label="Rekapitulasi per tanggal" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Tanggal</th>
                            <th scope="col" class="px-3 py-3 text-center font-medium">Pesanan Produk</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Nilai Produk</th>
                            <th scope="col" class="px-3 py-3 text-center font-medium">Pesanan Jasa</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Nilai Jasa</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($baris as $item)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3.5 font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-3 py-3.5 text-center text-gray-600">{{ $item->jumlah_produk }}</td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-right text-gray-800">{{ $formatUang($item->nilai_produk) }}</td>
                                <td class="px-3 py-3.5 text-center text-gray-600">{{ $item->jumlah_jasa }}</td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-right text-gray-800">{{ $formatUang($item->nilai_jasa) }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right font-medium text-gray-900">{{ $formatUang($item->total_nilai) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                                    Tidak ada pesanan pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50 text-sm font-semibold text-gray-900">
                        <tr>
                            <td class="px-5 py-3">Total</td>
                            <td class="px-3 py-3 text-center">{{ $total->jumlah_produk }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right">{{ $formatUang($total->nilai_produk) }}</td>
                            <td class="px-3 py-3 text-center">{{ $total->jumlah_jasa }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right">{{ $formatUang($total->nilai_jasa) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">{{ $formatUang($total->total_nilai) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
@endsection
