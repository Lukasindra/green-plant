<?php

// Pengaturan toko. GANTI data contoh di bawah dengan data toko yang sebenarnya.
// Metode pembayaran harus sama dengan CHECK pada kolom pesanan_*.metode_pembayaran di database
// (transfer, qris, cod).
return [
    'metode' => [
        'qris'     => 'QRIS',
        'transfer' => 'Transfer Bank',
        'cod'      => 'COD (Bayar di Tempat)',
    ],

    // DATA CONTOH
    'rekening' => [
        ['bank' => 'BCA', 'nomor' => '1234567890', 'nama' => 'Green Plant'],
        ['bank' => 'BNI', 'nomor' => '0987654321', 'nama' => 'Green Plant'],
    ],

    // Taruh gambar QRIS di public/images/qris.png (kalau belum ada, tampil teks)
    'qris_gambar' => 'images/qris.png',
];
