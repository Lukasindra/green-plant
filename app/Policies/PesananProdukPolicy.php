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

    // Customer hanya boleh membatalkan pesanannya sendiri selama pembayaran
    // belum dikonfirmasi Midtrans (masih unpaid/failed) dan belum diproses admin.
    public function batalkan(Akun $akun, PesananProduk $pesanan): bool
    {
        return $pesanan->customer?->akun_id === $akun->id
            && $pesanan->masihBelumDibayar()
            && ! in_array($pesanan->status_pesanan ?? '', ['dibatalkan', 'dikirim', 'selesai'], true);
    }

    // Kurir hanya boleh menangani pesanan yang ditugaskan kepadanya.
    public function antar(Akun $akun, PesananProduk $pesanan): bool
    {
        $kurir = $akun->isKurir() ? $akun->karyawan : null;

        return $kurir !== null
            && $pesanan->kurir_id !== null
            && (int) $pesanan->kurir_id === (int) $kurir->id;
    }
}
