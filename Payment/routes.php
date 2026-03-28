<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Modules\Payment\Controllers\AdvertController;
use Modules\Payment\Controllers\OrderController;
use Modules\Payment\Controllers\PaymentController;
use Modules\Payment\Controllers\PaymentSettingController;

/*  Платежи */
Route::middleware('web')
    ->prefix('payments')
    ->group(function () {
        Route::post('/webhook', [PaymentController::class, 'webhook'])
            ->withoutMiddleware(PreventRequestForgery::class);

        Route::get('/advert', [AdvertController::class, 'index']);
        Route::post('/calculate', [AdvertController::class, 'calculate']);
        Route::post('/pay', [AdvertController::class, 'pay']);
        Route::get('/status', [AdvertController::class, 'status']);
    });

/* Админка */
Route::middleware('web')
    ->prefix('admin')
    ->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/payment-settings', [PaymentSettingController::class, 'index']);
        Route::post('/payment-settings', [PaymentSettingController::class, 'save']);
    });
