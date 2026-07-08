<?php

use Illuminate\Support\Facades\Route;
use Modules\Caches\Http\Controllers\Admin\CacheController;

/* Админ — очистка кеша */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->controller(CacheController::class)
    ->prefix('admin/caches')
    ->name('admin.caches.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/clear', 'clear')->name('clear');
    });
