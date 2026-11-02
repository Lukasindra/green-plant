<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Membuat 10 tabel persis seperti ERD / logical design (kolom, NOT NULL, UNIQUE, DEFAULT, CHECK)
 * di database SQLite test. Jadi kalau kode memakai kolom atau nilai yang tidak ada di ERD
 * (misalnya metode 'ewallet' atau status 'diproses'), test langsung gagal.
 *
 * Foreign key sengaja tidak dibuat supaya data test cukup berisi id yang dibutuhkan saja.
 */
trait MembuatSkemaErd
{
    protected function buatSkemaErd(): void
    {
        $tabel = [
            "CREATE TABLE akun (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                no_hp VARCHAR(20),
                role VARCHAR(10) NOT NULL CHECK (role IN ('customer','kurir','admin'))
            )",
            "CREATE TABLE customer (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                akun_id INTEGER NOT NULL UNIQUE,
                alamat TEXT
            )",
            "CREATE TABLE karyawan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                akun_id INTEGER NOT NULL UNIQUE,
                username VARCHAR(50) NOT NULL UNIQUE
            )",
            "CREATE TABLE kategori (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(60) NOT NULL,
                tipe VARCHAR(10) NOT NULL CHECK (tipe IN ('produk','jasa')),
                UNIQUE (nama, tipe)
            )",
            "CREATE TABLE produk (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kategori_id INTEGER NOT NULL,
                nama VARCHAR(100) NOT NULL,
                deskripsi TEXT,
                harga DECIMAL(12,0) NOT NULL CHECK (harga >= 0),
                stok INTEGER NOT NULL DEFAULT 0 CHECK (stok >= 0),
                foto VARCHAR(255)
            )",
            "CREATE TABLE jasa (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kategori_id INTEGER NOT NULL,
                nama VARCHAR(100) NOT NULL,
                deskripsi TEXT,
                harga DECIMAL(12,0) NOT NULL CHECK (harga >= 0),
                satuan VARCHAR(20) NOT NULL DEFAULT 'paket' CHECK (satuan IN ('paket','m2','kunjungan')),
                foto VARCHAR(255)
            )",
            "CREATE TABLE pesanan_produk (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(20) NOT NULL UNIQUE,
                customer_id INTEGER NOT NULL,
                admin_id INTEGER NULL,
                kurir_id INTEGER NULL,
                tanggal_pesan DATE NOT NULL,
                total_bayar DECIMAL(12,0) NOT NULL DEFAULT 0,
                metode_pembayaran VARCHAR(10) NOT NULL CHECK (metode_pembayaran IN ('transfer','qris','cod')),
                status_pembayaran VARCHAR(20) NOT NULL DEFAULT 'menunggu_bayar'
                    CHECK (status_pembayaran IN ('menunggu_bayar','menunggu_verifikasi','lunas')),
                status_pesanan VARCHAR(20) NOT NULL DEFAULT 'menunggu_pembayaran'
                    CHECK (status_pesanan IN ('menunggu_pembayaran','dibayar','dikirim','selesai','dibatalkan')),
                alamat_pengiriman TEXT NOT NULL,
                bukti_bayar VARCHAR(255),
                tanggal_bayar DATE,
                tanggal_kirim DATE
            )",
            "CREATE TABLE detail_pesanan_produk (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pesanan_produk_id INTEGER NOT NULL,
                produk_id INTEGER NOT NULL,
                qty INTEGER NOT NULL CHECK (qty > 0),
                harga DECIMAL(12,0) NOT NULL,
                subtotal DECIMAL(12,0) NOT NULL
            )",
            "CREATE TABLE pesanan_jasa (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(20) NOT NULL UNIQUE,
                customer_id INTEGER NOT NULL,
                admin_id INTEGER NULL,
                tanggal_pesan DATE NOT NULL,
                total_bayar DECIMAL(12,0) NOT NULL DEFAULT 0,
                metode_pembayaran VARCHAR(10) NOT NULL CHECK (metode_pembayaran IN ('transfer','qris','cod')),
                status_pembayaran VARCHAR(20) NOT NULL DEFAULT 'menunggu_bayar'
                    CHECK (status_pembayaran IN ('menunggu_bayar','menunggu_verifikasi','lunas')),
                status_pesanan VARCHAR(20) NOT NULL DEFAULT 'menunggu_konfirmasi'
                    CHECK (status_pesanan IN ('menunggu_konfirmasi','dijadwalkan','dikerjakan','selesai','dibatalkan')),
                alamat_lokasi TEXT NOT NULL,
                jadwal_pengerjaan TIMESTAMP,
                tanggal_selesai TIMESTAMP,
                catatan TEXT,
                bukti_bayar VARCHAR(255),
                tanggal_bayar DATE
            )",
            "CREATE TABLE detail_pesanan_jasa (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pesanan_jasa_id INTEGER NOT NULL,
                jasa_id INTEGER NOT NULL,
                qty INTEGER NOT NULL CHECK (qty > 0),
                harga DECIMAL(12,0) NOT NULL,
                subtotal DECIMAL(12,0) NOT NULL
            )",
        ];

        foreach ($tabel as $sql) {
            DB::statement($sql);
        }
    }
}
