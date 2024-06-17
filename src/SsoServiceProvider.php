<?php

namespace ModusDigital\LaravelMicrosoftSso;

use Illuminate\Support\ServiceProvider;

class SsoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->commands([
            Commands\SsoPromptCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/sso.php' => config_path('sso.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
