@props([
    'varian' => 'primary',
    'ukuran' => 'md',
    'href' => null,
])
@php
    $warna = match ($varian) {
        'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-50',
        'brand-outline' => 'border border-brand-700 text-brand-700 hover:bg-brand-50',
        'danger' => 'border border-red-200 text-red-700 hover:bg-red-50',
        'sukses' => 'bg-emerald-600 text-white hover:bg-emerald-700',
        default => 'bg-brand-700 text-white hover:bg-brand-800',
    };
    $bentuk = $ukuran === 'sm' ? 'rounded-lg px-3 py-1.5 text-xs' : 'rounded-lg px-4 py-2.5 text-sm';
    $kelas = 'inline-flex items-center justify-center gap-1.5 font-semibold transition ' . $bentuk . ' ' . $warna;
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</button>
@endif
