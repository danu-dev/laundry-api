<?php

namespace App\Providers;

use App\Services\Notifications\MockWhatsAppProvider;
use App\Services\Notifications\NotificationProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NotificationProvider::class, MockWhatsAppProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
