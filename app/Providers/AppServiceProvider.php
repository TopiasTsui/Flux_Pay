<?php

namespace App\Providers;

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
     *
     * Order event listeners are registered automatically by Laravel 11's
     * listener discovery (it scans app/Listeners and reads each handle()
     * type-hint). Do NOT also register them manually with Event::listen
     * here — that double-registers every listener (e.g. LogOrderStatusChange
     * would write each order log twice).
     */
    public function boot(): void
    {
        //
    }
}
