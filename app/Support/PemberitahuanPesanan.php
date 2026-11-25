<?php

namespace App\Support;

use App\Mail\PesananMail;
use Illuminate\Support\Facades\Mail;

/**
 * Pengirim email transaksional pesanan.
 *
 * Kegagalan email (SMTP belum dikonfigurasi, alamat kosong) tidak boleh
 * menggagalkan checkout atau perubahan status pembayaran.
 */
class PemberitahuanPesanan
{
    public static function kirim($pesanan, string $jenis, string $tipe = 'produk'): void
    {
        try {
            $email = $pesanan->customer?->akun?->email;

            if (blank($email)) {
                return;
            }

            Mail::to($email)->send(new PesananMail($pesanan, $jenis, $tipe));
        } catch (\Throwable $e) {
            if (! app()->runningUnitTests()) {
                report($e);
            }
        }
    }
}
