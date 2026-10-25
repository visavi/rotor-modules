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
                Route::get('/', 'index')->name('admin.backups.index');
                Route::get('/create', 'create')->name('admin.backups.create');
                Route::post('/create', 'store');
                Route::delete('/delete', 'delete')->name('admin.backups.delete');
            });
    });
