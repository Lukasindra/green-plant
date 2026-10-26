<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PesananJasa extends Model
{
    protected $table = 'pesanan_jasa';
    public $timestamps = false;

    protected $fillable = ['kode', 'customer_id', 'tanggal_pesan', 'metode_pembayaran', 'alamat_lokasi', 'catatan'];

    protected function casts(): array
    {
        return [
            'total_bayar'       => 'integer',
            'tanggal_pesan'     => 'date',
            'tanggal_bayar'     => 'date',
            'jadwal_pengerjaan' => 'datetime',
            'tanggal_selesai'   => 'datetime',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function admin(): BelongsTo { return $this->belongsTo(Karyawan::class, 'admin_id'); }
    public function detail(): HasMany { return $this->hasMany(DetailPesananJasa::class, 'pesanan_jasa_id'); }
}
