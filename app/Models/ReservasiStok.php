<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stok yang "dipesan" saat checkout tetapi belum dibayar.
 * Stok fisik (produk.stok) baru berkurang setelah pembayaran lunas; reservasi
 * mencegah pembeli lain mengambil stok yang sama selama menunggu pembayaran.
 */
class ReservasiStok extends Model
{
    protected $table = 'reservasi_stok';

    public $timestamps = false;

    protected $fillable = [
        'pesanan_produk_id',
        'produk_id',
        'qty',
        'kedaluwarsa_pada',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'kedaluwarsa_pada' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
