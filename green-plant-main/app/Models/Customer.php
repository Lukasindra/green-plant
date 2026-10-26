<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'customer';
    public $timestamps = false;
    protected $fillable = ['akun_id', 'alamat'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'akun_id'); }
    public function pesananProduk(): HasMany { return $this->hasMany(PesananProduk::class, 'customer_id'); }
    public function pesananJasa(): HasMany { return $this->hasMany(PesananJasa::class, 'customer_id'); }
}
