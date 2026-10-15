<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    protected $table = 'karyawan';
    public $timestamps = false;
    protected $fillable = ['akun_id', 'username'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'akun_id'); }

    // sebagai admin
    public function pesananProdukDiproses(): HasMany { return $this->hasMany(PesananProduk::class, 'admin_id'); }
    public function pesananJasaDiproses(): HasMany { return $this->hasMany(PesananJasa::class, 'admin_id'); }

    // sebagai kurir
    public function pesananProdukDiantar(): HasMany { return $this->hasMany(PesananProduk::class, 'kurir_id'); }
}
