<?php

use Illuminate\Support\Facades\Route;
use Modules\HomeBuilder\Http\Controllers\Admin\BlockController;

Route::middleware(['web', 'check.admin:admin', 'admin.logger'])
    ->prefix('admin/home-builder')
    ->controller(BlockController::class)
    ->name('admin.home-builder.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::match(['get', 'post'], '/create', 'create')->name('create');
        Route::post('/sort', 'sort')->name('sort');
        Route::post('/preset', 'preset')->name('preset');
        Route::post('/activate', 'activate')->name('activate');
        Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
        Route::post('/{id}/customize', 'customize')->name('customize');
        Route::delete('/{id}', 'delete')->name('delete');
    });
