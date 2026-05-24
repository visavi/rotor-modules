<?php

use Illuminate\Support\Facades\Route;
use Modules\StyleEditor\Http\Controllers\Admin\EditorController;

/* Редактор CSS/JS */
Route::middleware(['web', 'check.admin:admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(EditorController::class)
            ->prefix('editor')
            ->name('admin.editor.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'save')->name('save');
            });
    });
