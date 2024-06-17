<?php

use Illuminate\Support\Facades\Route;
use ModusDigital\LaravelMicrosoftSso\Http\Controllers\MicrosoftSsoController;

Route::name('sso.')
    ->prefix('sso')
    ->group(function () {
        Route::get('login', [MicrosoftSsoController::class, 'login'])->name('login');
        Route::post('acs', [MicrosoftSsoController::class, 'acs'])->name('acs');
        Route::get('metadata', [MicrosoftSsoController::class, 'metadata'])->name('metadata');
        Route::post('sls', [MicrosoftSsoController::class, 'sls'])->name('sls');
        Route::get('sls', [MicrosoftSsoController::class, 'sls'])->name('sls');
        Route::get('logout', [MicrosoftSsoController::class, 'logout'])->name('logout');
    });
