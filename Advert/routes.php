<?php

use Illuminate\Support\Facades\Route;
use Modules\Advert\Http\Controllers\Admin\AdminAdvertController;
use Modules\Advert\Http\Controllers\Admin\AdvertController as AdminUserAdvertController;
use Modules\Advert\Http\Controllers\Admin\SettingController;
use Modules\Advert\Http\Controllers\AdvertController;

Route::controller(AdvertController::class)
    ->prefix('adverts')
    ->group(function () {
        Route::get('/', 'index');
        Route::match(['get', 'post'], '/create', 'create');
    });

Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(AdminAdvertController::class)
            ->prefix('admin-adverts')
            ->group(function () {
                Route::match(['get', 'post'], '/', 'index');
                Route::delete('/delete', 'delete');
            });

        Route::get('/adverts', [AdminUserAdvertController::class, 'index']);

        Route::middleware('check.admin:admin')->group(function () {
            Route::controller(AdminUserAdvertController::class)
                ->prefix('adverts')
                ->group(function () {
                    Route::match(['get', 'post'], '/edit/{id}', 'edit');
                    Route::post('/delete', 'delete');
                });
        });

        Route::get('/advert-settings', [SettingController::class, 'index'])->name('advert.settings');
        Route::post('/advert-settings', [SettingController::class, 'update'])->name('advert.settings.update');
    });
