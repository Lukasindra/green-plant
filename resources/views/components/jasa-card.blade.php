@props(['jasa'])
<a href="{{ route('jasa.show', $jasa) }}"
   class="group flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
    <div class="aspect-[4/3] overflow-hidden bg-gray-50">
        <x-gambar-jasa :foto="$jasa->foto" :nama="$jasa->nama" />
    </div>
    <div class="flex flex-1 flex-col p-3">
        <span class="truncate text-xs font-medium text-teal-600">{{ $jasa->kategori?->nama }}</span>
        <h3 class="line-clamp-2 mt-0.5 text-sm font-semibold leading-snug text-gray-900 group-hover:text-brand-700">
            {{ $jasa->nama }}
        </h3>
        <p class="mt-auto pt-2 text-sm font-semibold text-brand-700">
            Rp {{ number_format($jasa->harga, 0, ',', '.') }}
            <span class="text-xs font-normal text-gray-400">/ {{ $jasa->satuan }}</span>
        </p>
    </div>
</a>
