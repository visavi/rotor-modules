<?php

use Illuminate\Support\Facades\Route;
use Modules\Lottery\Controllers\IndexController;

/* Лотерея */
Route::middleware('web')
    ->prefix('lottery')
    ->group(function () {
        Route::get('/', [IndexController::class, 'index']);
        Route::post('/buy', [IndexController::class, 'buy']);
    });
