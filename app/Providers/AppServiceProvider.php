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
        // Enregistrer le service SMS selon l'environnement
        $this->app->bind(\App\Contracts\SmsServiceInterface::class, function ($app) {
            if (config('app.env') === 'production') {
                return new \App\Services\TwilioSmsService();
            } else {
                return new \App\Services\LogSmsService();
            }
        });

        // Enregistrer le service de base de données externe (Neon)
        $this->app->bind(\App\Contracts\DatabaseServiceInterface::class, function ($app) {
            return new \App\Services\NeonDatabaseService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
