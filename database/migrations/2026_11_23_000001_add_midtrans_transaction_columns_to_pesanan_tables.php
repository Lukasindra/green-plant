<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom identitas transaksi Midtrans, dipakai untuk:
 *  - mencari pesanan saat webhook masuk (order_id)
 *  - menampilkan/menyimpan transaction_id dan jenis pembayaran
 *  - mengecek status transaksi ke API Midtrans (polling)
 *
 * Database Supabase lama kemungkinan sudah punya kolom ini; migration dibuat
 * idempotent supaya aman dijalankan di database mana pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['pesanan_produk', 'pesanan_jasa'] as $tabel) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $t) use ($tabel) {
                if (! Schema::hasColumn($tabel, 'midtrans_order_id')) {
                    $t->string('midtrans_order_id')->nullable()->index();
                }

                if (! Schema::hasColumn($tabel, 'midtrans_transaction_id')) {
                    $t->string('midtrans_transaction_id')->nullable();
                }

                if (! Schema::hasColumn($tabel, 'midtrans_payment_type')) {
                    $t->string('midtrans_payment_type')->nullable();
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
                if (Schema::hasColumn($tabel, 'midtrans_payment_type')) {
                    $t->dropColumn('midtrans_payment_type');
                }

                if (Schema::hasColumn($tabel, 'midtrans_transaction_id')) {
                    $t->dropColumn('midtrans_transaction_id');
                }

                if (Schema::hasColumn($tabel, 'midtrans_order_id')) {
                    $t->dropColumn('midtrans_order_id');
                }
            });
        }
    }
};
