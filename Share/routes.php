<?php

use Illuminate\Support\Facades\Route;
use Modules\Share\Http\Controllers\Admin\ShareSettingController;

/* Админ — настройки */
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->controller(ShareSettingController::class)
    ->prefix('admin')
    ->name('share.')
    ->group(function () {
        Route::get('/share-settings', 'index')->name('settings');
        Route::post('/share-settings', 'update')->name('settings.update');
    });
