<?php

namespace App\Support;

use App\Models\Akun;
use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Models\Produk;
use Closure;
use Illuminate\Database\QueryException;

/**
 * Notifikasi lonceng, dihitung langsung dari data pesanan (tanpa tabel tambahan).
 * Isinya berupa "pekerjaan yang menunggu", jadi otomatis hilang setelah ditangani.
 */
class Notifikasi
{
    public const BATAS_STOK_RENDAH = 5;

    /**
     * @return array{total: int, item: list<array{judul: string, keterangan: string, jumlah: int, url: string}>}
     */
    public static function untuk(?Akun $akun): array
    {
        $item = match (true) {
            $akun === null => [],
            $akun->isAdmin() => self::untukAdmin(),
            $akun->isKurir() => self::untukKurir($akun),
            default => [],
        };

        return [
            'total' => array_sum(array_column($item, 'jumlah')),
            'item' => $item,
        ];
    }

    private static function untukAdmin(): array
    {
        $gagal = self::hitung(fn () => PesananProduk::query()->where('status_pembayaran', 'failed')->count())
            + self::hitung(fn () => PesananJasa::query()->where('status_pembayaran', 'failed')->count());

        $siapKirim = self::hitung(fn () => PesananProduk::query()
            ->where('status_pembayaran', 'paid')
            ->whereIn('status_pesanan', ['dibayar', 'diproses', 'menunggu_pembayaran'])
            ->count());

        $perluJadwal = self::hitung(fn () => PesananJasa::query()
            ->where('status_pembayaran', 'paid')
            ->whereNotIn('status_pesanan', ['dijadwalkan', 'dikerjakan', 'selesai', 'dibatalkan'])
            ->count());

        $stokRendah = self::hitung(fn () => Produk::query()->where('stok', '<=', self::BATAS_STOK_RENDAH)->count());

        $perluTindakan = self::hitung(fn () => PesananProduk::query()
            ->where('status_pesanan', 'perlu_tindakan')
            ->count());

        return self::susun([
            ['Pesanan perlu tindakan', 'Sudah dibayar tetapi stok tidak mencukupi (perlu refund/penyesuaian)', $perluTindakan, route('admin.pesanan.index', ['tipe' => 'produk', 'status' => 'perlu_tindakan'])],
            ['Pembayaran gagal', 'Pesanan gagal/kedaluwarsa dibayar, periksa statusnya', $gagal, route('admin.pembayaran.index', ['jenis' => 'failed'])],
            ['Pesanan siap dikirim', 'Pilih kurir lalu tandai dikirim', $siapKirim, route('admin.pengiriman.index')],
            ['Jasa perlu dijadwalkan', 'Sudah dibayar, belum ada jadwal pengerjaan', $perluJadwal, route('admin.pesanan.index', ['tipe' => 'jasa', 'status' => 'paid'])],
            ['Produk stok menipis', 'Stok '.self::BATAS_STOK_RENDAH.' atau kurang', $stokRendah, route('admin.produk.index')],
        ]);
    }

    private static function untukKurir(Akun $akun): array
    {
        $kurir = $akun->karyawan;

        if (! $kurir) {
            return [];
        }

        $diantar = self::hitung(fn () => PesananProduk::query()
            ->where('kurir_id', $kurir->id)
            ->where('status_pesanan', 'dikirim')
            ->count());

        return self::susun([
            ['Pesanan perlu diantar', 'Ditugaskan admin kepada Anda', $diantar, route('kurir.index')],
        ]);
    }

    /** Hanya simpan baris yang jumlahnya lebih dari nol. */
    private static function susun(array $baris): array
    {
        $item = [];

        foreach ($baris as [$judul, $keterangan, $jumlah, $url]) {
            if ($jumlah > 0) {
                $item[] = compact('judul', 'keterangan', 'jumlah', 'url');
            }
        }

        return $item;
    }

    // Lonceng hanya pelengkap: kalau satu hitungan gagal (misalnya tabel belum ada), halaman tetap tampil.
    private static function hitung(Closure $hitungan): int
    {
        try {
            return (int) $hitungan();
        } catch (QueryException $e) {
            if (! app()->runningUnitTests()) {
                report($e);
            }

            return 0;
        }
    }
}
