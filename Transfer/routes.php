<?php

use Illuminate\Support\Facades\Route;
use Modules\Transfer\Http\Controllers\Admin\TransferController as AdminTransferController;
use Modules\Transfer\Http\Controllers\Admin\TransferSettingController;
use Modules\Transfer\Http\Controllers\TransferController;

/* Перевод денег */
Route::middleware('web')
    ->controller(TransferController::class)
    ->prefix('transfers')
    ->name('transfers.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/send', 'send')->name('send');
    });

/* Админ — денежные переводы */
Route::middleware(['web', 'check.admin:moder', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(AdminTransferController::class)
            ->prefix('transfers')
            ->name('admin.transfers.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/view', 'view')->name('view');
            });

        Route::controller(TransferSettingController::class)
            ->name('transfer.')
            ->group(function () {
                Route::get('/transfer-settings', 'index')->name('settings');
                Route::post('/transfer-settings', 'update')->name('settings.update');
            });
    });
