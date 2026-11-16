@if ($tampil)
    <details class="relative" data-lonceng>
        <summary aria-label="Notifikasi{{ $total > 0 ? ', '.$total.' menunggu' : '' }}"
            class="relative flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 [&::-webkit-details-marker]:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10.3 20a1.9 1.9 0 0 0 3.4 0"/></svg>
            @if ($total > 0)
                <span class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">{{ $total > 99 ? '99+' : $total }}</span>
            @endif
        </summary>

        <div class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
            <p class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-900">Notifikasi</p>

            @forelse ($item as $n)
                <a href="{{ $n['url'] }}" class="flex items-start gap-3 px-4 py-3 transition hover:bg-gray-50">
                    <span class="mt-0.5 flex h-6 min-w-6 items-center justify-center rounded-full bg-brand-100 px-1.5 text-xs font-semibold text-brand-800">{{ $n['jumlah'] }}</span>
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-gray-900">{{ $n['judul'] }}</span>
                        <span class="block text-xs text-gray-500">{{ $n['keterangan'] }}</span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500">Tidak ada yang perlu ditindaklanjuti.</p>
            @endforelse
        </div>
    </details>

    @once
        <script>
            // Tutup panel lonceng saat klik di luar
            document.addEventListener('click', function (e) {
                document.querySelectorAll('details[data-lonceng][open]').forEach(function (d) {
                    if (!d.contains(e.target)) d.removeAttribute('open');
                });
            });
        </script>
    @endonce
@endif
