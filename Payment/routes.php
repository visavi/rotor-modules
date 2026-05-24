<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\AdvertController;
use Modules\Payment\Http\Controllers\OrderController;
use Modules\Payment\Http\Controllers\PaidAdvertController;
use Modules\Payment\Http\Controllers\PaymentController;
use Modules\Payment\Http\Controllers\PaymentSettingController;

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

        Route::controller(PaidAdvertController::class)
            ->prefix('paid-adverts')
            ->group(function () {
                Route::get('/', 'index');
                Route::match(['get', 'post'], '/create', 'create');
                Route::match(['get', 'post'], '/edit/{id}', 'edit');
                Route::delete('/delete/{id}', 'delete');
            });
    });
