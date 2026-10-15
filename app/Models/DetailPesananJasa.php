<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPesananJasa extends Model
{
    protected $table = 'detail_pesanan_jasa';
    public $timestamps = false;
    protected $fillable = ['pesanan_jasa_id', 'jasa_id', 'qty', 'harga', 'subtotal'];

    protected function casts(): array
    {
        return ['qty' => 'integer', 'harga' => 'integer', 'subtotal' => 'integer'];
    }

    public function pesanan(): BelongsTo { return $this->belongsTo(PesananJasa::class, 'pesanan_jasa_id'); }
    public function jasa(): BelongsTo { return $this->belongsTo(Jasa::class, 'jasa_id'); }
}
