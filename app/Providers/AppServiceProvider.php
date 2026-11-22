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
        // Modelo de IA del asistente del bot de Telegram (gratis con la capa gratuita de Gemini)
        $this->app->bind(\App\Services\Telegram\IA\ModeloIA::class, \App\Services\Telegram\IA\GeminiModeloIA::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
