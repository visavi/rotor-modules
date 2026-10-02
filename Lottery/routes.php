<?php

use Illuminate\Support\Facades\Route;
use Modules\Lottery\Http\Controllers\Admin\LotterySettingController;
use Modules\Lottery\Http\Controllers\IndexController;

/* Лотерея */
Route::middleware('web')
    ->prefix('lottery')
    ->group(function () {
        Route::get('/', [IndexController::class, 'index']);
        Route::post('/buy', [IndexController::class, 'buy']);
    });

/* Админ — настройки лотереи */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->controller(LotterySettingController::class)
    ->name('lottery.')
    ->group(function () {
        Route::get('/lottery-settings', 'index')->name('settings');
        Route::post('/lottery-settings', 'update')->name('settings.update');
    });
