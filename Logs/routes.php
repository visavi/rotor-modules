<?php

use Illuminate\Support\Facades\Route;
use Modules\Logs\Http\Controllers\Admin\LogController;

/* Админ — логи посещений */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->controller(LogController::class)
    ->prefix('admin/logs')
    ->name('admin.logs.')
    ->group(function () {
        Route::get('/', 'index')->name('index')->withoutMiddleware('admin.logger');
        Route::post('/clear', 'clear')->name('clear');
    });
