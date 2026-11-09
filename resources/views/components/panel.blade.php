@props([
    'judul' => null,
    'subjudul' => null,
])
<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if ($judul)
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">{{ $judul }}</h2>
            @if ($subjudul)
                <p class="mt-1 text-xs text-gray-500">{{ $subjudul }}</p>
            @endif
        </div>
    @endif
    {{ $slot }}
</section>
