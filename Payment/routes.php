<?php

use Illuminate\Support\Facades\Route;

/*  Платежи */
Route::middleware('web')
    ->prefix('payments')
    ->group(function () {
        Route::post('/webhook', [\Modules\Payment\Controllers\PaymentController::class, 'webhook']);

        Route::get('/advert', [\Modules\Payment\Controllers\AdvertController::class, 'index']);
        Route::post('/calculate', [\Modules\Payment\Controllers\AdvertController::class, 'calculate']);
        Route::post('/pay', [\Modules\Payment\Controllers\AdvertController::class, 'pay']);
        Route::get('/status', [\Modules\Payment\Controllers\AdvertController::class, 'status']);
    });

/* Заказы */
Route::middleware('web')
    ->prefix('admin')
    ->group(function () {
        Route::get('/orders', [\Modules\Payment\Controllers\OrderController::class, 'index']);
    });
