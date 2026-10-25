<?php

use Illuminate\Support\Facades\Route;
use Modules\Gift\Http\Controllers\Api\GiftApiController;
use Modules\Gift\Http\Controllers\GiftSettingController;
use Modules\Gift\Http\Controllers\IndexController;
use Modules\Gift\Http\Controllers\PanelController;

/* Подарки */
Route::middleware('web')
    ->prefix('gifts')
    ->group(function () {
        Route::get('/', [IndexController::class, 'index'])->name('gifts.index');
        Route::get('/send/{id}', [IndexController::class, 'send'])->middleware('check.user')->name('gifts.send');
        Route::post('/send/{id}', [IndexController::class, 'store'])->middleware('check.user');
        Route::get('/{login}', [IndexController::class, 'gifts'])->name('gifts.user');
    });

Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/gifts', [PanelController::class, 'index'])->name('admin.gifts.index');
        Route::post('/gifts', [PanelController::class, 'update']);
        Route::delete('/gifts/{id}', [PanelController::class, 'delete'])->name('admin.gifts.delete');

        Route::controller(GiftSettingController::class)
            ->name('gift.')
            ->group(function () {
                Route::get('/gift-settings', 'index')->name('settings');
                Route::post('/gift-settings', 'update')->name('settings.update');
            });
    });

/* ---- API роуты ---- */
// Каталог и подарки в профиле открыты, как и страницы раздела
Route::middleware(['api', 'check.token.optional'])
    ->prefix('api')
    ->group(function () {
        Route::get('/gifts', [GiftApiController::class, 'index']);
        Route::get('/gifts/{login}', [GiftApiController::class, 'user']);
    });

// Отправка стоит денег — только со своим токеном
Route::middleware(['api', 'check.token'])
    ->prefix('api')
    ->group(function () {
        Route::post('/gifts/{id}/send', [GiftApiController::class, 'send'])->whereNumber('id');
    });
