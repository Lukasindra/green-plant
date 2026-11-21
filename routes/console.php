<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Batalkan pesanan yang belum dibayar dan sudah kedaluwarsa (perlu scheduler: php artisan schedule:work)
Schedule::command('pesanan:batalkan-kedaluwarsa')->dailyAt('01:00');
