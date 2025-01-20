<?php

use Illuminate\Support\Facades\Route;

/*  Платежи */
Route::group(['prefix' => 'payments'], function () {
    Route::match(['get', 'post'], '/advert', [\Modules\Payment\Controllers\AdvertController::class, 'index']);
});

Route::group(['prefix' => 'admin'], function () {
    Route::get('/orders', [\Modules\Payment\Controllers\OrderController::class, 'index']);
});
