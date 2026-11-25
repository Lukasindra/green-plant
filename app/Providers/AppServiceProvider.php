<?php

namespace App\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nama bulan/hari mengikuti locale aplikasi (translatedFormat).
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));

        Paginator::defaultView('pagination.admin');
        Paginator::defaultSimpleView('pagination.admin');
    }
}
