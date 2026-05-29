<?php

use Illuminate\Support\Facades\Route;
use Modules\Phpinfo\Http\Controllers\Admin\PhpInfoController;

/* Админ — PHP-информация */
Route::middleware(['web', 'check.admin:admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/phpinfo', [PhpInfoController::class, 'index'])->name('admin.phpinfo');
    });
