<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Akun extends Authenticatable
{
    use Notifiable;

    protected $table = 'akun';
    public $timestamps = false;

    // 'role' sengaja TIDAK di fillable agar tidak bisa diubah lewat input user
    protected $fillable = ['nama', 'email', 'password', 'no_hp'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function customer(): HasOne { return $this->hasOne(Customer::class, 'akun_id'); }
    public function karyawan(): HasOne { return $this->hasOne(Karyawan::class, 'akun_id'); }

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isKurir(): bool    { return $this->role === 'kurir'; }
    public function isCustomer(): bool { return $this->role === 'customer'; }
}
