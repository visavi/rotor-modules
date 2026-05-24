<?php

use Illuminate\Support\Facades\Route;
use Modules\Backup\Http\Controllers\Admin\BackupController;

/* Backup */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(BackupController::class)
            ->prefix('backups')
            ->group(function () {
                Route::get('/', 'index');
                Route::match(['get', 'post'], '/create', 'create');
                Route::delete('/delete', 'delete');
            });
    });
