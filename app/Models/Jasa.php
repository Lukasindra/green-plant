<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jasa extends Model
{
    protected $table = 'jasa';
    public $timestamps = false;
    protected $fillable = ['kategori_id', 'nama', 'deskripsi', 'harga', 'satuan', 'foto'];

    protected function casts(): array
    {
        return ['harga' => 'integer'];
    }

    public function kategori(): BelongsTo { return $this->belongsTo(Kategori::class, 'kategori_id'); }
}
