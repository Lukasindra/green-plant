@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman"
        class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1">
            {{-- Tombol halaman sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Halaman sebelumnya"
                    class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-600 transition hover:bg-gray-100 hover:text-brand-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @endif

            {{-- Nomor halaman --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="px-1.5 text-sm text-gray-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-brand-700 px-2 text-sm font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Ke halaman {{ $page }}"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-brand-800">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Tombol halaman berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-600 transition hover:bg-gray-100 hover:text-brand-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Halaman berikutnya"
                    class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            @endif
        </div>

        <p class="text-xs text-gray-500">
            Halaman <span class="font-medium text-gray-700">{{ $paginator->currentPage() }}</span>
            dari {{ $paginator->lastPage() }}
            &mdash; {{ number_format($paginator->total()) }} data
        </p>
    </nav>
@endif
