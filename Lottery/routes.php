<?php

use Illuminate\Support\Facades\Route;

/* Лотерея */
Route::middleware('web')
    ->prefix('lottery')
    ->group(function () {
        Route::get('/', [\Modules\Lottery\Controllers\IndexController::class, 'index']);
        Route::post('/buy', [\Modules\Lottery\Controllers\IndexController::class, 'buy']);
    });
