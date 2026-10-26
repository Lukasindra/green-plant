<?php

// Pengaturan toko. GANTI data contoh di bawah dengan data toko yang sebenarnya.
return [
    'ongkir' => 15000,   // ongkos kirim tetap per pesanan (rupiah)

    'metode' => [
        'qris'     => 'QRIS',
        'ewallet'  => 'E-Wallet',
        'transfer' => 'Transfer Bank',
        'cod'      => 'COD (Bayar di Tempat)',
    ],

    // DATA CONTOH
    'rekening' => [
        ['bank' => 'BCA', 'nomor' => '1234567890', 'nama' => 'Green Plant'],
        ['bank' => 'BNI', 'nomor' => '0987654321', 'nama' => 'Green Plant'],
    ],
    'ewallet' => [
        ['nama' => 'OVO',    'nomor' => '081200000000'],
        ['nama' => 'GoPay',  'nomor' => '081200000000'],
        ['nama' => 'DANA',   'nomor' => '081200000000'],
    ],

    // Taruh gambar QRIS di public/images/qris.png (kalau belum ada, tampil teks)
    'qris_gambar' => 'images/qris.png',
];
