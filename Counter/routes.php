<?php

use Illuminate\Support\Facades\Route;
use Modules\Counter\Http\Controllers\Admin\CounterSettingController;
use Modules\Counter\Http\Controllers\CounterController;

/* Статистика посещений */
Route::middleware('web')
    ->get('/counters', [CounterController::class, 'index']);

/* Админ — настройки */
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->controller(CounterSettingController::class)
    ->prefix('admin')
    ->name('counter.')
    ->group(function () {
        Route::get('/counter-settings', 'index')->name('settings');
        Route::post('/counter-settings', 'update')->name('settings.update');
    });
