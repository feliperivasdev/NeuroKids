<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Registrar el servicio de progresión automática
        $this->app->singleton(\App\Services\ProgresionAutomaticaService::class, function ($app) {
            return new \App\Services\ProgresionAutomaticaService();
        });
    }
}
