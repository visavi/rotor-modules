<?php

use Illuminate\Support\Facades\Route;
use Modules\Docs\Http\Controllers\DocsController;
use Modules\Docs\Http\Controllers\RotorController;

Route::middleware('web')->group(function () {
    Route::controller(RotorController::class)
        ->prefix('rotor')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/releases', 'releases');
            Route::get('/commits', 'commits');
        });

    Route::controller(DocsController::class)
        ->prefix('docs')
        ->group(function () {
            Route::get('/search', 'search');
            Route::get('/{page?}', 'show');
        });
});
