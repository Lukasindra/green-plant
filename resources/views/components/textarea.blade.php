@props([
    'nama',
    'label' => null,
    'baris' => 4,
])
@php
    $id = $attributes->get('id', $nama);
@endphp
<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif
    <textarea {{ $attributes->merge(['id' => $id, 'name' => $nama, 'rows' => $baris,
        'class' => 'block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600']) }}>{{ $slot }}</textarea>
    @error($nama) <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>
