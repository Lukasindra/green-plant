@extends('layouts.app')

@section('title', 'Green Plant — Temukan tanaman favoritmu')

@section('content')
    @php
        $products = [
            [
                'name' => 'Monstera Deliciosa',
                'category' => 'Tanaman indoor',
                'price' => 125000,
                'badge' => 'Favorit',
                'icon' => '🌿',
                'tone' => 'sage',
            ],
            [
                'name' => 'Sansevieria',
                'category' => 'Mudah dirawat',
                'price' => 85000,
                'badge' => 'Pemula',
                'icon' => '🪴',
                'tone' => 'peach',
            ],
            [
                'name' => 'Calathea Orbifolia',
                'category' => 'Tanaman indoor',
                'price' => 155000,
                'badge' => 'Pilihan',
                'icon' => '🍃',
                'tone' => 'lavender',
            ],
            [
                'name' => 'Paket Plant Care',
                'category' => 'Layanan perawatan',
                'price' => 75000,
                'badge' => 'Layanan',
                'icon' => '🌱',
                'tone' => 'butter',
            ],
        ];
    @endphp

    <section class="hero-section">
        <div class="page-container hero-grid">
            <div class="hero-copy">
                <p class="eyebrow"><span></span> TEMAN BARU UNTUK RUANGMU</p>
                <h1>Hadirkan alam,<br>di <span>setiap sudut.</span></h1>
                <p class="hero-description">Tanaman pilihan yang bikin rumah terasa lebih hidup. Temukan teman hijau yang cocok untukmu.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="#produk">Temukan tanaman <span aria-hidden="true">↗</span></a>
                    <a class="text-link" href="#layanan">Lihat layanan kami</a>
                </div>
                <div class="hero-proof">
                    <div class="avatar-stack" aria-hidden="true"><span>🌼</span><span>🪴</span><span>🌱</span></div>
                    <p><strong>1.200+</strong><br>rumah sudah lebih hijau</p>
                </div>
            </div>

            <div class="hero-art" aria-label="Ilustrasi tanaman hias">
                <div class="hero-circle"></div>
                <span class="hero-sparkle sparkle-one" aria-hidden="true">✳</span>
                <span class="hero-sparkle sparkle-two" aria-hidden="true">✦</span>
                <div class="hero-plant" aria-hidden="true">🌿</div>
                <div class="hero-plant-label"><span>pilihan minggu ini</span><strong>Monstera deliciosa</strong></div>
            </div>
        </div>
    </section>

    <section class="benefit-strip" aria-label="Keunggulan Green Plant">
        <div class="page-container benefit-grid">
            <div class="benefit-item"><span aria-hidden="true">☀</span><p><strong>Tanaman pilihan</strong><br>Dirawat dengan sepenuh hati</p></div>
            <div class="benefit-item"><span aria-hidden="true">♧</span><p><strong>Siap menemani</strong><br>Dikemas aman sampai rumah</p></div>
            <div class="benefit-item"><span aria-hidden="true">♡</span><p><strong>Teman bertumbuh</strong><br>Panduan perawatan sederhana</p></div>
        </div>
    </section>

    <section class="catalog-section page-container" id="produk">
        <div class="section-heading">
            <div>
                <p class="eyebrow"><span></span> PILIHAN UNTUKMU</p>
                <h2>Temukan <span>favoritmu</span></h2>
                <p class="section-description">Sedikit hijau bisa mengubah suasana seluruh ruangan.</p>
            </div>
            <a class="text-link catalog-link" href="#produk">Lihat semua <span aria-hidden="true">→</span></a>
        </div>

        <div class="product-grid">
            @foreach ($products as $product)
                @include('component.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>

    <section class="service-section" id="layanan">
        <div class="page-container service-content">
            <div class="service-illustration" aria-hidden="true">🪴</div>
            <div>
                <p class="eyebrow"><span></span> BUTUH SEDIKIT BANTUAN?</p>
                <h2>Tanaman bahagia,<br><span>rumah pun ceria.</span></h2>
                <p class="section-description">Kami siap membantu memilih dan merawat tanaman agar tumbuh subur di rumahmu.</p>
                <a class="button button-primary" href="#produk">Kenali layanan kami <span aria-hidden="true">↗</span></a>
            </div>
        </div>
    </section>
@endsection
