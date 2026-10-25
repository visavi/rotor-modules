<?php

use Illuminate\Support\Facades\Route;
use Modules\Advert\Http\Controllers\Admin\AdminAdvertController;
use Modules\Advert\Http\Controllers\Admin\AdvertController as AdminUserAdvertController;
use Modules\Advert\Http\Controllers\Admin\SettingController;
use Modules\Advert\Http\Controllers\AdvertController;

Route::middleware(['web', 'check.user'])
    ->controller(AdvertController::class)
    ->prefix('adverts')
    ->group(function () {
        Route::get('/', 'index')->name('adverts.index');
        Route::get('/create', 'create')->name('adverts.create');
        Route::post('/create', 'store');
    });

Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(AdminAdvertController::class)
            ->prefix('admin-adverts')
            ->group(function () {
                Route::get('/', 'index')->name('admin.admin-adverts.index');
                Route::post('/', 'store');
                Route::delete('/delete', 'delete')->name('admin.admin-adverts.delete');
            });

        Route::get('/adverts', [AdminUserAdvertController::class, 'index'])->name('admin.adverts.index');

        Route::middleware('check.admin:admin')->group(function () {
            Route::controller(AdminUserAdvertController::class)
                ->prefix('adverts')
                ->group(function () {
                    Route::get('/edit/{id}', 'edit')->name('admin.adverts.edit');
                    Route::post('/edit/{id}', 'update');
                    Route::post('/delete', 'delete')->name('admin.adverts.delete');
                });
        });

        Route::get('/advert-settings', [SettingController::class, 'index'])->name('advert.settings');
        Route::post('/advert-settings', [SettingController::class, 'update'])->name('advert.settings.update');
    });
