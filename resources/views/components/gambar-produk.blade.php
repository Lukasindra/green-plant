@props(['foto' => null, 'nama' => ''])
@php
    $file = $foto ? 'images/produk/' . basename($foto) : null;
    $ada = $file && file_exists(public_path($file));
@endphp
@if ($ada)
    <img src="{{ asset($file) }}" alt="{{ $nama }}" class="h-full w-full object-cover" loading="lazy">
@else
    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-50 to-brand-100">
        <x-logo-icon class="h-14 w-14 text-brand-500/50" />
    </div>
@endif
