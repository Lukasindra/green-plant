<?php

namespace App\Support;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Schema;

/**
 * Membaca pengaturan toko yang diisi admin (tabel `pengaturan`),
 * dengan cadangan ke config/toko.php bila tabel/isinya belum ada.
 * Pembayaran diproses Midtrans, jadi rekening/e-wallet toko tidak lagi dipakai.
 */
class Toko
{
    private const KUNCI = ['ongkir'];

    private static function tersimpan(): array
    {
        try {
            if (! Schema::hasTable('pengaturan')) {
                return [];
            }

            return Pengaturan::query()->whereIn('kunci', self::KUNCI)->pluck('nilai', 'kunci')->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private static function baca(string $kunci, mixed $bawaan): mixed
    {
        $semua = self::tersimpan();

        if (array_key_exists($kunci, $semua)) {
            $nilai = json_decode((string) $semua[$kunci], true);

            if ($nilai !== null) {
                return $nilai;   // 0 (ongkir gratis) tetap dianggap nilai sah
            }
        }

        return $bawaan;
    }

    public static function ongkir(): int
    {
        return max(0, (int) self::baca('ongkir', config('toko.ongkir', 0)));
    }
}
