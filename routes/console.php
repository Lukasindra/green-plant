<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rekonsiliasi pembayaran Midtrans: menangkap VA/e-wallet yang selesai setelah
// halaman pembayaran ditutup atau saat webhook tidak sampai ke server.
Schedule::command('pesanan:sinkronkan-pembayaran')->everyTenMinutes()->withoutOverlapping();

// Batalkan pesanan yang belum dibayar dan sudah kedaluwarsa (perlu scheduler: php artisan schedule:work)
Schedule::command('pesanan:batalkan-kedaluwarsa')->dailyAt('01:00')->withoutOverlapping();
