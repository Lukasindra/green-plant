<?php

namespace App\Support;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Schema;

/**
 * Membaca pengaturan toko yang diisi admin (tabel `pengaturan`),
 * dengan cadangan ke config/toko.php bila tabel/isinya belum ada.
 */
class Toko
{
    private const KUNCI = ['ongkir', 'rekening', 'ewallet'];

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
                return $nilai;   // 0 (ongkir gratis) dan [] (daftar kosong) tetap dianggap nilai sah
            }
        }

        return $bawaan;
    }

    public static function ongkir(): int
    {
        return max(0, (int) self::baca('ongkir', config('toko.ongkir', 0)));
    }

    public static function rekening(): array
    {
        return (array) self::baca('rekening', config('toko.rekening', []));
    }

    public static function ewallet(): array
    {
        return (array) self::baca('ewallet', config('toko.ewallet', []));
    }
}
