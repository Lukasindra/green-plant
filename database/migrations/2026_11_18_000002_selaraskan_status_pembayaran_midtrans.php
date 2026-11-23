<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyelaraskan struktur database lama dengan pembayaran Midtrans.
 *
 * Migration 000001 hanya MENAMBAH kolom. Database Supabase yang sudah ada
 * dibuat dari skema manual sehingga masih membawa aturan lama:
 *  - metode_pembayaran NOT NULL + CHECK (transfer/qris/ewallet/cod)
 *  - status_pembayaran CHECK (menunggu_bayar/menunggu_verifikasi/lunas)
 *
 * Tanpa migration ini checkout gagal dengan SQLSTATE[23502] (kolom NOT NULL),
 * dan setelah kolom itu beres insert tetap gagal karena 'unpaid'/'paid'/'failed'
 * tidak diizinkan oleh CHECK lama.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tabel = ['pesanan_produk', 'pesanan_jasa'];

    public function up(): void
    {
        foreach ($this->tabel as $tabel) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            // 1. Kolom pembayaran manual tidak dipakai lagi.
            if (Schema::hasColumn($tabel, 'metode_pembayaran')) {
                if ($this->postgres()) {
                    DB::statement("ALTER TABLE \"{$tabel}\" ALTER COLUMN metode_pembayaran DROP NOT NULL");
                    DB::statement("ALTER TABLE \"{$tabel}\" DROP CONSTRAINT IF EXISTS \"{$tabel}_metode_pembayaran_check\"");
                } else {
                    Schema::table($tabel, function (Blueprint $t) {
                        $t->string('metode_pembayaran')->nullable()->change();
                    });
                }
            }

            if (! Schema::hasColumn($tabel, 'status_pembayaran')) {
                continue;
            }

            // 2. Batasan lama hanya mengenal status manual; lepaskan dulu sebelum datanya diubah.
            if ($this->postgres()) {
                DB::statement("ALTER TABLE \"{$tabel}\" DROP CONSTRAINT IF EXISTS \"{$tabel}_status_pembayaran_check\"");
            }

            // 3. Samakan data lama dengan istilah Midtrans.
            DB::table($tabel)->where('status_pembayaran', 'menunggu_bayar')->update(['status_pembayaran' => 'unpaid']);
            DB::table($tabel)->where('status_pembayaran', 'menunggu_verifikasi')->update(['status_pembayaran' => 'unpaid']);
            DB::table($tabel)->where('status_pembayaran', 'lunas')->update(['status_pembayaran' => 'paid']);

            // Jaring pengaman: nilai lain yang tidak dikenal dianggap belum dibayar
            // supaya penambahan CHECK di bawah tidak gagal karena satu baris lama.
            DB::table($tabel)
                ->whereNotIn('status_pembayaran', ['unpaid', 'paid', 'failed'])
                ->update(['status_pembayaran' => 'unpaid']);

            if ($this->postgres()) {
                DB::statement(
                    "ALTER TABLE \"{$tabel}\" ADD CONSTRAINT \"{$tabel}_status_pembayaran_check\" "
                    . "CHECK (status_pembayaran IN ('unpaid', 'paid', 'failed'))"
                );
            }
        }

        // 4. Checkout jasa memakai 'menunggu_pembayaran'; CHECK lama tidak mengizinkannya.
        if (Schema::hasTable('pesanan_jasa')
            && Schema::hasColumn('pesanan_jasa', 'status_pesanan')
            && $this->postgres()) {
            DB::statement('ALTER TABLE "pesanan_jasa" DROP CONSTRAINT IF EXISTS "pesanan_jasa_status_pesanan_check"');
            DB::statement(
                'ALTER TABLE "pesanan_jasa" ADD CONSTRAINT "pesanan_jasa_status_pesanan_check" '
                . "CHECK (status_pesanan IN ('menunggu_pembayaran', 'menunggu_konfirmasi', 'diproses', "
                . "'dijadwalkan', 'dikerjakan', 'selesai', 'dibatalkan'))"
            );
        }
    }

    /**
     * Tidak dibalik: status pembayaran lama sudah dikonversi dan kolom
     * metode_pembayaran sudah boleh NULL. Mengembalikan aturan lama justru
     * akan menolak data yang sekarang sah.
     */
    public function down(): void
    {
        //
    }

    private function postgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }
};
