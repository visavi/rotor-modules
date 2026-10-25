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
            ->withoutMiddleware(PreventRequestForgery::class)
            ->name('payments.webhook');

        Route::get('/advert', [AdvertController::class, 'index'])->name('payments.advert');
        Route::post('/calculate', [AdvertController::class, 'calculate'])->name('payments.calculate');
        Route::post('/pay', [AdvertController::class, 'pay'])->name('payments.pay');
        Route::get('/status', [AdvertController::class, 'status'])->name('payments.status');

        // Гостю страница карточек предлагает войти, а не отвечает 403
        Route::get('/card', [CardController::class, 'index'])->name('payments.card');
    });

/* Моя реклама (ссылку покупать может и гость, но редактировать только авторизованный владелец),
   карточки (только авторизованным: картинку не проверяют заранее, нарушителя должно быть кого забанить)
   и пополнение баланса: монеты зачисляются на счёт, гостю их некуда положить */
Route::middleware(['web', 'check.user'])
    ->prefix('payments')
    ->group(function () {
        Route::post('/card/calculate', [CardController::class, 'calculate'])->name('payments.card.calculate');
        Route::get('/my/card/{id}', [CardController::class, 'edit'])->name('payments.my.card');
        Route::post('/my/card/{id}', [CardController::class, 'update']);
        Route::get('/my/card/renew/{id}', [CardController::class, 'renew'])->name('payments.my.card.renew');

        Route::get('/my', [AdvertController::class, 'my'])->name('payments.my');
        Route::get('/my/edit/{id}', [AdvertController::class, 'edit'])->name('payments.my.edit');
        Route::post('/my/edit/{id}', [AdvertController::class, 'update']);
        Route::get('/my/renew/{id}', [AdvertController::class, 'renew'])->name('payments.my.renew');

        Route::get('/balance', [BalanceController::class, 'index'])->name('payments.balance');
        Route::post('/balance', [BalanceController::class, 'pay']);
    });

/* Админка */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
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
                Route::get('/', 'index')->name('admin.paid-adverts.index');
                Route::get('/create', 'create')->name('admin.paid-adverts.create');
                Route::post('/create', 'store');
                Route::get('/edit/{id}', 'edit')->name('admin.paid-adverts.edit');
                Route::post('/edit/{id}', 'update');
                Route::get('/create-card', 'createCard')->name('admin.paid-adverts.create-card');
                Route::post('/create-card', 'storeCard');
                Route::get('/edit-card/{id}', 'editCard')->name('admin.paid-adverts.edit-card');
                Route::post('/edit-card/{id}', 'updateCard');
                Route::post('/stop/{id}', 'stop')->name('admin.paid-adverts.stop');
                Route::delete('/delete/{id}', 'delete')->name('admin.paid-adverts.delete');
            });
    });
