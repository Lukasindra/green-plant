<?php

namespace App\Models;

use App\Models\Concerns\DibayarViaMidtrans;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PesananJasa extends Model
{
    use DibayarViaMidtrans;

    protected $table = 'pesanan_jasa';
    public $timestamps = false;

    protected $fillable = [
        'kode',
        'invoice_number',
        'snap_token',
        'customer_id',
        'tanggal_pesan',
        'alamat_lokasi',
        'catatan',
    ];

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
    public function riwayat(): MorphMany { return $this->morphMany(RiwayatPesanan::class, 'pesanan'); }
}
