<?php

namespace App\Support;

use App\Models\RiwayatPesanan;
use Illuminate\Support\Facades\Schema;

/**
 * Pencatat riwayat status pesanan.
 *
 * Ditulis sebagai helper statis dan selalu dibungkus try/catch supaya aplikasi
 * tetap berjalan di database lama yang belum punya tabel `riwayat_pesanan`.
 */
class CatatanPesanan
{
    public static function catat($pesanan, string $status, ?string $keterangan = null, ?int $aktorId = null): void
    {
        try {
            if (! Schema::hasTable('riwayat_pesanan')) {
                return;
            }

            RiwayatPesanan::create([
                'pesanan_type' => $pesanan::class,
                'pesanan_id' => $pesanan->getKey(),
                'status' => $status,
                'keterangan' => $keterangan,
                'aktor_id' => $aktorId ?? auth()->id(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Riwayat hanya pelengkap; jangan gagalkan checkout/pembayaran.
            if (! app()->runningUnitTests()) {
                report($e);
            }
        }
    }

    /** Label Indonesia untuk status yang disimpan di riwayat. */
    public static function label(string $status): string
    {
        return match ($status) {
            'dibuat' => 'Pesanan dibuat',
            'lunas' => 'Pembayaran diterima',
            'gagal' => 'Pembayaran gagal',
            'dibatalkan' => 'Pesanan dibatalkan',
            'dikirim' => 'Pesanan dikirim',
            'dijadwalkan' => 'Jadwal pengerjaan ditetapkan',
            'dikerjakan' => 'Pengerjaan dimulai',
            'selesai' => 'Pesanan selesai',
            'perlu_tindakan' => 'Perlu tindakan admin',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
