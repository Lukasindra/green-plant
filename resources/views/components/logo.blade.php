@props(['terang' => false])
<span class="inline-flex items-center gap-2">
    <x-logo-icon class="h-8 w-8 {{ $terang ? 'text-white' : 'text-brand-700' }}" />
    <span class="text-sm font-semibold leading-tight {{ $terang ? 'text-white' : 'text-brand-800' }}">Green<br>Plant</span>
</span>
