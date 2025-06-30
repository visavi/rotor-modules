<?php

use Illuminate\Support\Facades\Route;
use Modules\Docs\Controllers\DocsController;
use Modules\Docs\Controllers\RotorController;

Route::middleware('web')
    ->controller(RotorController::class)
    ->prefix('rotor')
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/releases', 'releases');
        Route::get('/commits', 'commits');
    });

Route::middleware('web')
    ->controller(DocsController::class)
    ->prefix('docs')
    ->group(function () {
        Route::get('/', 'index');
    });
