@extends('layouts.admin')

@section('judul', 'Pengiriman')

@section('isi')
    @php
        $statusTab = [
            'siap' => 'Siap Kirim',
            'dikirim' => 'Dikirim',
            'selesai' => 'Selesai',
        ];
        $formatUang = fn ($nilai) => 'Rp ' . number_format((float) $nilai, 0, ',', '.');
    @endphp

    <div class="space-y-6">
        {{-- Filter status --}}
        <div>
            <div class="flex items-center gap-2">
                @foreach ($statusTab as $kunci => $label)
                    <a href="{{ route('admin.pengiriman.index', ['status' => $kunci]) }}"
                        @if ($status === $kunci) aria-current="page" @endif
                        class="rounded-full px-4 py-1.5 text-sm font-medium {{ $status === $kunci ? 'bg-brand-700 text-white' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <x-panel>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Pesanan</th>
                            <th scope="col" class="px-3 py-3 font-medium">Alamat Kirim</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Nilai</th>
                            <th scope="col" class="px-3 py-3 font-medium">Kurir</th>
                            <th scope="col" class="px-3 py-3 font-medium">Tanggal Kirim</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($pesanan as $item)
                            <tr>
                                <td class="px-5 py-4 align-top">
                                    <span class="block font-medium text-gray-900">{{ $item->kode }}</span>
                                    <span class="mt-1 block text-xs text-gray-500">{{ $item->customer?->akun?->nama ?? 'Tanpa nama' }}</span>
                                    <span class="mt-1 block"><x-status-badge :nilai="$item->status_pesanan ?? 'diproses'" /></span>
                                </td>
                                <td class="max-w-[16rem] px-3 py-4 align-top text-gray-600">
                                    {{ $item->alamat_pengiriman ?: 'Tanpa alamat' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right align-top font-medium text-gray-800">
                                    {{ $formatUang($item->total_bayar) }}
                                </td>
                                <td class="px-3 py-4 align-top text-gray-600">
                                    {{ $item->kurir?->username ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 align-top text-gray-600">
                                    {{ $item->tanggal_kirim?->translatedFormat('d M Y') ?? '-' }}
                                </td>
                                <td class="px-5 py-4 text-right align-top">
                                    @if ($status === 'siap')
                                        <form method="POST" action="{{ route('admin.pengiriman.aksi', ['pesanan' => $item]) }}"
                                            class="flex flex-col items-end gap-2 sm:flex-row sm:items-center sm:justify-end">
                                            @csrf
                                            @method('POST')
                                            <input type="hidden" name="aksi" value="kirim">
                                            <select name="kurir_id" aria-label="Pilih kurir"
                                                class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                                                <option value="">Tanpa kurir</option>
                                                @foreach ($daftarKurir as $kurir)
                                                    <option value="{{ $kurir->id }}">{{ $kurir->username }}</option>
                                                @endforeach
                                            </select>
                                            <x-button ukuran="sm">Tandai Dikirim</x-button>
                                        </form>
                                    @elseif ($status === 'dikirim')
                                        <form method="POST" action="{{ route('admin.pengiriman.aksi', ['pesanan' => $item]) }}">
                                            @csrf
                                            <input type="hidden" name="aksi" value="selesai">
                                            <x-button varian="sukses" ukuran="sm">Tandai Selesai</x-button>
                                        </form>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-14 text-center">
                                    <p class="font-semibold text-gray-800">
                                        Tidak ada pesanan pada status {{ strtolower($statusTab[$status]) }}.
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500">Pilih tab status lain di atas untuk melihat daftar pengiriman lainnya.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($pesanan->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $pesanan->links() }}
                </div>
            @endif
        </x-panel>
    </div>
@endsection
