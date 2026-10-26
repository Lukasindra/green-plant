<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $table = 'kategori';
    public $timestamps = false;
    protected $fillable = ['nama', 'tipe'];

    public function produk(): HasMany { return $this->hasMany(Produk::class, 'kategori_id'); }
    public function jasa(): HasMany { return $this->hasMany(Jasa::class, 'kategori_id'); }
}
