<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- Tambahan untuk memanggil fitur URL

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
        // <-- Logika tambahan untuk mengatasi Mixed Content (Memaksa HTTPS)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
