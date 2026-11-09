@extends('layouts.app')

@section('judul', 'Tugas Antar')

@section('isi')
    @php
        $tab = [
            'dikirim' => 'Perlu diantar',
            'selesai' => 'Riwayat selesai',
        ];
    @endphp

    <div class="mb-4">
        <h1 class="text-xl font-semibold">Tugas Antar</h1>
        <p class="text-sm text-gray-500">Pesanan produk yang ditugaskan admin kepada Anda.</p>
    </div>

    <div class="mb-4 flex items-center gap-2">
        @foreach ($tab as $kunci => $label)
            <a href="{{ route('kurir.index', ['status' => $kunci]) }}"
               @if ($status === $kunci) aria-current="page" @endif
               class="rounded-full px-4 py-1.5 text-sm font-medium {{ $status === $kunci ? 'bg-brand-700 text-white' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @forelse ($pesanan as $item)
        <a href="{{ route('kurir.show', $item) }}"
           class="mb-3 block rounded-xl border border-gray-200 bg-white p-4 hover:border-brand-300">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="font-semibold">{{ $item->kode }}</span>
                <x-status-badge :nilai="$item->status_pesanan" />
            </div>
            <p class="mt-1 text-sm text-gray-700">{{ $item->customer?->akun?->nama ?? '-' }}</p>
            <p class="text-sm text-gray-500">{{ $item->alamat_pengiriman }}</p>
            <p class="mt-2 text-sm">
                {{ $item->detail->sum('qty') }} barang
                <span class="text-gray-400">&middot;</span>
                Rp {{ number_format($item->total_bayar, 0, ',', '.') }}
                @if ($item->metode_pembayaran === 'cod')
                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">COD</span>
                @endif
            </p>
        </a>
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
            @if ($status === 'dikirim')
                Belum ada pesanan yang perlu diantar.
            @else
                Belum ada riwayat pengantaran.
            @endif
        </div>
    @endforelse

    <div class="mt-4">{{ $pesanan->links() }}</div>
@endsection
