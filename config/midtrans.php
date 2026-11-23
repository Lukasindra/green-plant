<?php

// Konfigurasi Midtrans Snap. Kredensial diambil dari .env agar tidak tertanam di kode.
// Mode Sandbox: MIDTRANS_IS_PRODUCTION=false (popup membuka app.sandbox.midtrans.com).
return [
    'merchant_id'   => env('MIDTRANS_MERCHANT_ID'),
    'client_key'    => env('MIDTRANS_CLIENT_KEY'),
    'server_key'    => env('MIDTRANS_SERVER_KEY'),
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),

    // Bersihkan parameter sebelum dikirim ke Midtrans
    'is_sanitized'  => true,

    // Aktifkan 3DS untuk kartu kredit di popup Snap
    'is_3ds'        => true,

    // Masa berlaku Snap token (jam). Setelah lewat, user memakai "Coba Bayar Ulang".
    'kedaluwarsa_jam' => 24,

    // URL webhook publik untuk menguji notifikasi (mis. https://xxxx.ngrok-free.app/midtrans/notifikasi).
    // Kosongkan bila memakai URL notifikasi dari dashboard Midtrans.
    'notification_url' => env('MIDTRANS_NOTIFICATION_URL'),
];
