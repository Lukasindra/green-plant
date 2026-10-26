<article class="product-card">
    <a class="product-image-link" href="#produk" aria-label="Lihat {{ $product['name'] }}">
        <div class="product-image product-image-{{ $product['tone'] }}">
            <span class="product-badge">{{ $product['badge'] }}</span>
            <span class="plant-illustration" aria-hidden="true">{{ $product['icon'] }}</span>
        </div>
    </a>
    <div class="product-details">
        <p class="product-category">{{ $product['category'] }}</p>
        <h3><a href="#produk">{{ $product['name'] }}</a></h3>
        <div class="product-bottom">
            <p class="product-price">Rp{{ number_format($product['price'], 0, ',', '.') }}</p>
            <a class="add-button" href="#produk" aria-label="Lihat {{ $product['name'] }}">+</a>
        </div>
    </div>
</article>
