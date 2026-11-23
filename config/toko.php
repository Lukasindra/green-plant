<?php

// Pengaturan toko. GANTI data contoh di bawah dengan data toko yang sebenarnya.
// Pembayaran diproses Midtrans Snap (lihat config/midtrans.php), jadi metode pembayaran
// manual (transfer/qris/ewallet/cod) sudah tidak dipakai lagi.
return [
    'ongkir' => 15000,   // ongkos kirim tetap per pesanan (rupiah)
];
