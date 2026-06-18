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
        $this->app->singleton(App\Services\ExpediteService::class, function ($app) {
            return new App\Services\ExpediteService();
        });
        
        $this->app->singleton(App\Services\StdSomedayService::class, function ($app) {
            return new App\Services\StdSomedayService();
        });
        
        $this->app->singleton(App\Services\ReminderCourierService::class, function ($app) {
            return new App\Services\ReminderCourierService();
        });
        
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
