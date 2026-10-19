<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\AdvertController;
use Modules\Payment\Http\Controllers\BalanceController;
use Modules\Payment\Http\Controllers\CardController;
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

        // Гостю страница карточек предлагает войти, а не отвечает 403
        Route::get('/card', [CardController::class, 'index']);
    });

/* Моя реклама (ссылку покупать может и гость, но редактировать только авторизованный владелец),
   карточки (только авторизованным: картинку не проверяют заранее, нарушителя должно быть кого забанить)
   и пополнение баланса: монеты зачисляются на счёт, гостю их некуда положить */
Route::middleware(['web', 'check.user'])
    ->prefix('payments')
    ->group(function () {
        Route::post('/card/calculate', [CardController::class, 'calculate']);
        Route::get('/my/card/{id}', [CardController::class, 'edit']);
        Route::post('/my/card/{id}', [CardController::class, 'update']);

        Route::get('/my', [AdvertController::class, 'my']);
        Route::get('/my/edit/{id}', [AdvertController::class, 'edit']);
        Route::post('/my/edit/{id}', [AdvertController::class, 'update']);

        Route::get('/balance', [BalanceController::class, 'index']);
        Route::post('/balance', [BalanceController::class, 'pay']);
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
                Route::get('/create', 'create');
                Route::post('/create', 'store');
                Route::get('/edit/{id}', 'edit');
                Route::post('/edit/{id}', 'update');
                Route::get('/create-card', 'createCard');
                Route::post('/create-card', 'storeCard');
                Route::get('/edit-card/{id}', 'editCard');
                Route::post('/edit-card/{id}', 'updateCard');
                Route::post('/stop/{id}', 'stop');
                Route::delete('/delete/{id}', 'delete');
            });
    });
