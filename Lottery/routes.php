<?php

use Illuminate\Support\Facades\Route;

/* Лотерея */
Route::group(['prefix' => 'lottery'], function () {
    Route::get('/', [\Modules\Lottery\Controllers\IndexController::class, 'index']);
    Route::post('/buy', [\Modules\Lottery\Controllers\IndexController::class, 'buy']);
});
