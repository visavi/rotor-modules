<?php

use Illuminate\Support\Facades\Route;
use Modules\PageEditor\Http\Controllers\Admin\FileController;

/* Редактор страниц */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(FileController::class)
            ->prefix('files')
            ->group(function () {
                Route::get('/', 'index');
                Route::match(['get', 'post'], '/edit', 'edit');
                Route::match(['get', 'post'], '/create', 'create');
                Route::delete('/delete', 'delete');
            });
    });
