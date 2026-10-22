<?php

use Illuminate\Support\Facades\Route;
use Modules\HomeBuilder\Http\Controllers\Admin\BlockController;

Route::middleware(['web', 'check.admin:admin', 'admin.logger'])
    ->prefix('admin/home-builder')
    ->controller(BlockController::class)
    ->name('admin.home-builder.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/create', 'store');
        Route::post('/sort', 'sort')->name('sort');
        Route::post('/preset', 'preset')->name('preset');
        Route::post('/activate', 'activate')->name('activate')->middleware('check.admin:boss');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::post('/{id}/edit', 'update');
        Route::post('/{id}/customize', 'customize')->name('customize')->middleware('check.admin:boss');
        Route::delete('/{id}', 'delete')->name('delete');
    });
