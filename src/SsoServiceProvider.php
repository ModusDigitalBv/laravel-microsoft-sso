<?php

namespace ModusDigital\LaravelMicrosoftSso;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
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
        ], 'sso-config');

        $this->publishes([
            __DIR__.'/../stubs/Controller.stub' => app_path('Http/Controllers/Auth/MicrosoftSsoController.php'),
        ], 'sso-controllers');

        // Load the routes
        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        $file = app_path('Http/Controllers/Auth/MicrosoftSsoController.php');

        File::exists($file)
            ? $this->loadPublishedRoutes()
            : $this->loadPackageRoutes();
    }

    protected function loadPublishedRoutes(): void
    {
        Route::middleware('web')
            ->namespace('App\Http\Controllers\Auth')
            ->name('sso.')
            ->prefix('sso')
            ->group(function () {
                Route::get('login', 'MicrosoftSsoController@login')->name('login');
                Route::post('acs', 'MicrosoftSsoController@acs')->name('acs');
                Route::get('metadata', 'MicrosoftSsoController@metadata')->name('metadata');
                Route::post('sls', 'MicrosoftSsoController@sls')->name('sls');
                Route::get('sls', 'MicrosoftSsoController@sls')->name('sls');
                Route::get('logout', 'MicrosoftSsoController@logout')->name('logout');
            });
    }

    protected function loadPackageRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
