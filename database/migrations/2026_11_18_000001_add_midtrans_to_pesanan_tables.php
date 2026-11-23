<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kolom pendukung pembayaran Midtrans Snap.
// user_id  -> sudah terwakili oleh customer_id (relasi customer -> akun)
// total    -> sudah terwakili oleh total_bayar
// Baru:    invoice_number (INV-YYYYMMDD-XXXX), snap_token, status pembayaran dipakai ulang
//          dengan nilai unpaid/paid/failed.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['pesanan_produk', 'pesanan_jasa'] as $tabel) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $t) use ($tabel) {
                if (! Schema::hasColumn($tabel, 'invoice_number')) {
                    $t->string('invoice_number')->nullable()->unique();
                }

                if (! Schema::hasColumn($tabel, 'snap_token')) {
                    $t->string('snap_token')->nullable();
                }

                // Metode pembayaran manual (transfer/qris/cod) tidak dipakai lagi.
                // Kolom lama dibuat NOT NULL; tanpa ini checkout baru gagal insert di database lama.
                if (Schema::hasColumn($tabel, 'metode_pembayaran')) {
                    $t->string('metode_pembayaran')->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['pesanan_produk', 'pesanan_jasa'] as $tabel) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $t) use ($tabel) {
                if (Schema::hasColumn($tabel, 'snap_token')) {
                    $t->dropColumn('snap_token');
                }

                if (Schema::hasColumn($tabel, 'invoice_number')) {
                    // Index unik ikut terhapus otomatis bersama kolomnya (Postgres/SQLite).
                    $t->dropColumn('invoice_number');
                }
            });
        }
    }
};
