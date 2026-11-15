@props(['foto' => null, 'nama' => ''])
@php
    $file = $foto ? 'images/jasa/' . basename($foto) : null;
    $ada = $file && file_exists(public_path($file));
@endphp
@if ($ada)
    <img src="{{ asset($file) }}" alt="{{ $nama }}" class="h-full w-full object-cover" loading="lazy">
@else
    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-teal-50 to-emerald-100">
        <x-logo-icon class="h-14 w-14 text-teal-500/50" />
    </div>
@endif
