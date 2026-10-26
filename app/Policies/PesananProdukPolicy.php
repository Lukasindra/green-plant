<?php

namespace App\Policies;

use App\Models\Akun;
use App\Models\PesananProduk;

class PesananProdukPolicy
{
    // Admin boleh melihat semua; customer hanya pesanannya sendiri.
    public function view(Akun $akun, PesananProduk $pesanan): bool
    {
        return $akun->isAdmin() || $pesanan->customer?->akun_id === $akun->id;
    }

    // Unggah bukti hanya oleh pemilik, selama belum lunas, belum dibatalkan, dan bukan COD.
    public function uploadBukti(Akun $akun, PesananProduk $pesanan): bool
    {
        return $pesanan->customer?->akun_id === $akun->id
            && in_array($pesanan->status_pembayaran, ['menunggu_bayar', 'menunggu_verifikasi'], true)
            && $pesanan->status_pesanan !== 'dibatalkan'
            && $pesanan->metode_pembayaran !== 'cod';
    }
}
