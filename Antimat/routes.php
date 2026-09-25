<?php

use Illuminate\Support\Facades\Route;
use Modules\Antimat\Http\Controllers\Admin\AntimatController;
use Modules\Antimat\Http\Controllers\Admin\AntimatSettingController;

/* Админ — антимат */
Route::middleware(['web', 'check.admin:moder', 'admin.logger'])
    ->controller(AntimatController::class)
    ->prefix('admin/antimat')
    ->name('admin.antimat.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::delete('/delete', 'delete')->name('delete');
        Route::post('/clear', 'clear')->name('clear');
    });

/* Админ — настройки антимата */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->controller(AntimatSettingController::class)
    ->name('antimat.')
    ->group(function () {
        Route::get('/antimat-settings', 'index')->name('settings');
        Route::post('/antimat-settings', 'update')->name('settings.update');
    });
