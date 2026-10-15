<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPesananProduk extends Model
{
    protected $table = 'detail_pesanan_produk';
    public $timestamps = false;
    protected $fillable = ['pesanan_produk_id', 'produk_id', 'qty', 'harga', 'subtotal'];

    protected function casts(): array
    {
        return ['qty' => 'integer', 'harga' => 'integer', 'subtotal' => 'integer'];
    }

    public function pesanan(): BelongsTo { return $this->belongsTo(PesananProduk::class, 'pesanan_produk_id'); }
    public function produk(): BelongsTo { return $this->belongsTo(Produk::class, 'produk_id'); }
}
