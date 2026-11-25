<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema dasar Green Plant.
 *
 * Database produksi (Supabase) sebelumnya dibuat manual sehingga repo ini tidak
 * bisa di-clone lalu langsung `php artisan migrate`. Migration ini membuat tabel
 * inti hanya bila belum ada, jadi aman dijalankan di database lama maupun baru.
 *
 * Kolom Midtrans ikut dibuat di sini; migration 000001/000003 tetap idempotent
 * dan hanya menambah kolom yang belum ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->buat('akun', function (Blueprint $t): void {
            $t->id();
            $t->string('nama');
            $t->string('email')->unique();
            $t->string('password');
            $t->string('no_hp')->nullable();
            $t->string('role')->default('customer');
            $t->rememberToken();
        });

        $this->buat('customer', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('akun_id')->index();
            $t->string('alamat', 500)->nullable();
        });

        $this->buat('karyawan', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('akun_id')->index();
            $t->string('username');
        });

        $this->buat('kategori', function (Blueprint $t): void {
            $t->id();
            $t->string('nama');
            $t->string('tipe')->default('produk');
        });

        $this->buat('produk', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('kategori_id')->nullable()->index();
            $t->string('nama');
            $t->text('deskripsi')->nullable();
            $t->unsignedInteger('harga')->default(0);
            $t->unsignedInteger('stok')->default(0);
            $t->string('foto')->nullable();
        });

        $this->buat('jasa', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('kategori_id')->nullable()->index();
            $t->string('nama');
            $t->text('deskripsi')->nullable();
            $t->unsignedInteger('harga')->default(0);
            $t->string('satuan')->nullable();
            $t->string('foto')->nullable();
        });

        $this->buat('pesanan_produk', function (Blueprint $t): void {
            $t->id();
            $t->string('kode')->unique();
            $t->string('invoice_number')->nullable()->unique();
            $t->string('snap_token')->nullable();
            $t->string('midtrans_order_id')->nullable()->index();
            $t->string('midtrans_transaction_id')->nullable();
            $t->string('midtrans_payment_type')->nullable();
            $t->unsignedBigInteger('customer_id')->index();
            $t->unsignedBigInteger('admin_id')->nullable();
            $t->unsignedBigInteger('kurir_id')->nullable();
            $t->date('tanggal_pesan');
            $t->date('tanggal_bayar')->nullable();
            $t->date('tanggal_kirim')->nullable();
            $t->string('metode_pembayaran')->nullable();
            $t->string('alamat_pengiriman', 500);
            $t->unsignedInteger('total_bayar')->default(0);
            $t->unsignedInteger('ongkir')->default(0);
            $t->string('status_pembayaran')->default('unpaid')->index();
            $t->string('status_pesanan')->default('menunggu_pembayaran')->index();
            $t->string('bukti_bayar')->nullable();
        });

        $this->buat('detail_pesanan_produk', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('pesanan_produk_id')->index();
            $t->unsignedBigInteger('produk_id')->nullable();
            $t->unsignedInteger('qty')->default(1);
            $t->unsignedInteger('harga')->default(0);
            $t->unsignedInteger('subtotal')->default(0);
        });

        $this->buat('pesanan_jasa', function (Blueprint $t): void {
            $t->id();
            $t->string('kode')->unique();
            $t->string('invoice_number')->nullable()->unique();
            $t->string('snap_token')->nullable();
            $t->string('midtrans_order_id')->nullable()->index();
            $t->string('midtrans_transaction_id')->nullable();
            $t->string('midtrans_payment_type')->nullable();
            $t->unsignedBigInteger('customer_id')->index();
            $t->unsignedBigInteger('admin_id')->nullable();
            $t->date('tanggal_pesan');
            $t->date('tanggal_bayar')->nullable();
            $t->timestamp('jadwal_pengerjaan')->nullable();
            $t->timestamp('tanggal_selesai')->nullable();
            $t->string('metode_pembayaran')->nullable();
            $t->string('alamat_lokasi', 500);
            $t->text('catatan')->nullable();
            $t->unsignedInteger('total_bayar')->default(0);
            $t->string('status_pembayaran')->default('unpaid')->index();
            $t->string('status_pesanan')->default('menunggu_pembayaran')->index();
            $t->string('bukti_bayar')->nullable();
        });

        $this->buat('detail_pesanan_jasa', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('pesanan_jasa_id')->index();
            $t->unsignedBigInteger('jasa_id')->nullable();
            $t->unsignedInteger('qty')->default(1);
            $t->unsignedInteger('harga')->default(0);
            $t->unsignedInteger('subtotal')->default(0);
        });

        $this->buat('pengaturan', function (Blueprint $t): void {
            $t->id();
            $t->string('kunci')->unique();
            $t->text('nilai')->nullable();
        });

        $this->buat('password_reset_tokens', function (Blueprint $t): void {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        $this->buat('riwayat_pesanan', function (Blueprint $t): void {
            $t->id();
            $t->string('pesanan_type');
            $t->unsignedBigInteger('pesanan_id');
            $t->string('status');
            $t->text('keterangan')->nullable();
            $t->unsignedBigInteger('aktor_id')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['pesanan_type', 'pesanan_id']);
        });

        $this->buat('reservasi_stok', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('pesanan_produk_id')->index();
            $t->unsignedBigInteger('produk_id')->index();
            $t->unsignedInteger('qty')->default(1);
            $t->timestamp('kedaluwarsa_pada');
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        // Tabel inti tidak dihapus otomatis: database produksi mungkin sudah ada
        // sebelum migration ini dan rollback tidak boleh menghapus data asli.
    }

    private function buat(string $tabel, callable $skema): void
    {
        if (Schema::hasTable($tabel)) {
            return;
        }

        Schema::create($tabel, $skema);
    }
};
