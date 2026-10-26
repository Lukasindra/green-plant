<header class="site-header">
    <div class="page-container nav-content">
        <a class="brand" href="{{ url('/') }}" aria-label="Green Plant, halaman utama">
            <span>green<span class="brand-light">plant</span></span>
        </a>

        <nav class="desktop-nav" aria-label="Navigasi utama">
            <a href="{{ url('/') }}">Beranda</a>
            <a href="#produk">Katalog</a>
            <a href="#layanan">Layanan</a>
            <a href="#tentang">Tentang kami</a>
        </nav>

        <a class="nav-action" href="#produk">
            <span class="nav-action-icon" aria-hidden="true">⌕</span>
            <span>Jelajahi</span>
        </a>

        @php
            $jumlahKeranjang = array_sum((array) session('cart', []));
        @endphp
        <a class="nav-action" href="{{ route('keranjang.index') }}" aria-label="Keranjang, {{ $jumlahKeranjang }} produk">
            <span aria-hidden="true">🛒</span>
            <span>Keranjang</span>
            @if ($jumlahKeranjang > 0)
                <span class="rounded-full bg-brand-700 px-2 py-0.5 text-xs text-white">{{ $jumlahKeranjang }}</span>
            @endif
        </a>
    </div>
</header>
