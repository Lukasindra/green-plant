<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu baris riwayat perubahan status pesanan (produk maupun jasa).
 * Ditulis lewat App\Support\CatatanPesanan agar tidak mengganggu alur utama
 * bila tabelnya belum tersedia di database lama.
 */
class RiwayatPesanan extends Model
{
    protected $table = 'riwayat_pesanan';

    public $timestamps = false;

    protected $fillable = [
        'pesanan_type',
        'pesanan_id',
        'status',
        'keterangan',
        'aktor_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function pesanan(): MorphTo
    {
        return $this->morphTo();
    }
}
