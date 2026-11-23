<?php

namespace App\Policies;

use App\Models\Akun;
use App\Models\PesananJasa;

class PesananJasaPolicy
{
    // Admin boleh melihat semua; customer hanya pesanannya sendiri.
    public function view(Akun $akun, PesananJasa $pesanan): bool
    {
        return $akun->isAdmin() || $pesanan->customer?->akun_id === $akun->id;
    }

    // Customer hanya boleh membatalkan pesanannya sendiri selama pembayaran
    // belum dikonfirmasi Midtrans (masih unpaid/failed) dan belum dijadwalkan/dikerjakan.
    public function batalkan(Akun $akun, PesananJasa $pesanan): bool
    {
        return $pesanan->customer?->akun_id === $akun->id
            && $pesanan->masihBelumDibayar()
            && ! in_array($pesanan->status_pesanan ?? '', ['dibatalkan', 'dijadwalkan', 'dikerjakan', 'selesai'], true);
    }
}
