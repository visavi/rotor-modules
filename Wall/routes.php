<?php

use Illuminate\Support\Facades\Route;
use Modules\Wall\Http\Controllers\Admin\SettingController;
use Modules\Wall\Http\Controllers\WallController;

Route::middleware('web')
    ->controller(WallController::class)
    ->prefix('walls')
    ->name('walls.')
    ->group(function () {
        Route::get('/{login}', 'index')->name('index');
        Route::post('/{login}/create', 'create')->name('create');
        Route::post('/{login}/delete', 'delete')->name('delete');
    });

Route::middleware(['web', 'check.admin:admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/wall-settings', [SettingController::class, 'index'])->name('wall.settings');
        Route::post('/wall-settings', [SettingController::class, 'update'])->name('wall.settings.update');
    });
