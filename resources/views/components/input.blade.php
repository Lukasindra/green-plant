@props([
    'nama',
    'label' => null,
    'tipe' => 'text',
    'wajib' => false,
    'bantuan' => null,
])
@php
    $id = $attributes->get('id', $nama);
@endphp
<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-gray-700">
            {{ $label }}@if ($wajib) <span class="text-red-600">*</span>@endif
        </label>
    @endif
    <input {{ $attributes->merge(['id' => $id, 'name' => $nama, 'type' => $tipe, 'required' => $wajib,
        'class' => 'block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600']) }}>
    @if ($bantuan)
        <p class="mt-1 text-xs text-gray-500">{{ $bantuan }}</p>
    @endif
    @error($nama) <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>
