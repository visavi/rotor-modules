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

/* Моя реклама (покупать может и гость, но редактировать только авторизованный владелец) */
Route::middleware(['web', 'check.user'])
    ->prefix('payments')
    ->group(function () {
        Route::get('/my', [AdvertController::class, 'my']);
        Route::get('/my/edit/{id}', [AdvertController::class, 'edit']);
        Route::post('/my/edit/{id}', [AdvertController::class, 'update']);
    });

/* Админка */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        /* Настройки */
        Route::controller(PaymentSettingController::class)
            ->name('payment.')
            ->group(function () {
                Route::get('/payment-settings', 'index')->name('settings');
                Route::post('/payment-settings', 'update')->name('settings.update');
            });

        Route::controller(PaidAdvertController::class)
            ->prefix('paid-adverts')
            ->group(function () {
                Route::get('/', 'index');
                Route::match(['get', 'post'], '/create', 'create');
                Route::match(['get', 'post'], '/edit/{id}', 'edit');
                Route::delete('/delete/{id}', 'delete');
            });
    });
