<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    protected $table = 'produk';
    public $timestamps = false;
    protected $fillable = ['kategori_id', 'nama', 'deskripsi', 'harga', 'stok', 'foto'];

    protected function casts(): array
    {
        return ['harga' => 'integer', 'stok' => 'integer'];
    }

    public function kategori(): BelongsTo { return $this->belongsTo(Kategori::class, 'kategori_id'); }
}
