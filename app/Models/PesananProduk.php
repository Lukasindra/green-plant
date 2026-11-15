<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PesananProduk extends Model
{
    protected $table = 'pesanan_produk';
    public $timestamps = false;

    // Status, total, ongkir, admin, dan kurir TIDAK di fillable: diisi lewat kode server, bukan input user
    protected $fillable = ['kode', 'customer_id', 'tanggal_pesan', 'metode_pembayaran', 'alamat_pengiriman'];

    protected function casts(): array
    {
        return [
            'total_bayar'   => 'integer',
            'ongkir'        => 'integer',
            'tanggal_pesan' => 'date',
            'tanggal_bayar' => 'date',
            'tanggal_kirim' => 'date',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function admin(): BelongsTo { return $this->belongsTo(Karyawan::class, 'admin_id'); }
    public function kurir(): BelongsTo { return $this->belongsTo(Karyawan::class, 'kurir_id'); }
    public function detail(): HasMany { return $this->hasMany(DetailPesananProduk::class, 'pesanan_produk_id'); }
}
