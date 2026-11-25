<?php

namespace App\Services;

use App\Exceptions\StokTidakCukup;
use App\Models\PesananProduk;
use App\Models\Produk;
use App\Models\ReservasiStok;
use Illuminate\Support\Facades\Schema;

/**
 * Reservasi stok saat checkout.
 *
 * Masalah yang dicegah: dua pembeli bisa checkout item terakhir bersamaan,
 * keduanya membayar, lalu stok fisik menjadi minus. Dengan reservasi, pembeli
 * kedua sudah ditolak saat checkout karena stok tersedia = stok fisik - reservasi
 * yang masih aktif.
 *
 * Stok fisik tetap baru berkurang saat pembayaran lunas (sesuai perilaku lama),
 * reservasi hanya "menahan" stok sampai pesanan dibayar/dibatalkan/kedaluwarsa.
 */
class ReservasiStokService
{
    /** Masa berlaku reservasi; mengikuti batas umum pembayaran Midtrans. */
    public const BERLAKU_JAM = 24;

    public function tersedia(): bool
    {
        return Schema::hasTable('reservasi_stok');
    }

    /**
     * Buat reservasi untuk seluruh baris pesanan. Harus dipanggil di dalam
     * transaksi database agar baris produk terkunci sampai reservasi tersimpan.
     *
     * @param  array<int, array{produk: Produk, qty: int}>  $baris
     *
     * @throws StokTidakCukup
     */
    public function pesan(PesananProduk $pesanan, array $baris): void
    {
        if (! $this->tersedia()) {
            return;
        }

        $sekarang = now();

        // Kunci semua produk lebih dulu, baru hitung, supaya dua checkout
        // bersamaan tidak sama-sama melihat stok yang sama.
        foreach ($baris as $b) {
            $produk = Produk::query()->whereKey($b['produk']->id)->lockForUpdate()->first();

            if (! $produk) {
                throw new StokTidakCukup('Produk pada keranjang sudah tidak tersedia.');
            }

            $direservasi = (int) ReservasiStok::query()
                ->where('produk_id', $produk->id)
                ->where('kedaluwarsa_pada', '>', $sekarang)
                ->sum('qty');

            if ($produk->stok - $direservasi < (int) $b['qty']) {
                throw new StokTidakCukup("Stok {$produk->nama} tidak mencukupi.");
            }
        }

        foreach ($baris as $b) {
            ReservasiStok::create([
                'pesanan_produk_id' => $pesanan->getKey(),
                'produk_id' => $b['produk']->id,
                'qty' => (int) $b['qty'],
                'kedaluwarsa_pada' => $sekarang->copy()->addHours(self::BERLAKU_JAM),
                'created_at' => $sekarang,
            ]);
        }
    }

    /** Reservasi selesai: stok fisik sudah dipotong, hapus penahannya. */
    public function konfirmasi($pesanan): void
    {
        $this->hapus($pesanan);
    }

    /** Pesanan dibatalkan/kedaluwarsa: lepaskan stok untuk pembeli lain. */
    public function lepas($pesanan): void
    {
        $this->hapus($pesanan);
    }

    /** Hapus reservasi yang sudah lewat masa berlaku (pembersihan berkala). */
    public function bersihkanKedaluwarsa(): int
    {
        if (! $this->tersedia()) {
            return 0;
        }

        return ReservasiStok::query()->where('kedaluwarsa_pada', '<=', now())->delete();
    }

    private function hapus($pesanan): void
    {
        if (! $this->tersedia() || ! $pesanan?->getKey()) {
            return;
        }

        ReservasiStok::query()->where('pesanan_produk_id', $pesanan->getKey())->delete();
    }
}
