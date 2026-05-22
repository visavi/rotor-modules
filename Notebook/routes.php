<?php

use Illuminate\Support\Facades\Route;
use Modules\Notebook\Http\Controllers\NotebookController;

/* Блокнот */
Route::middleware('web')
    ->controller(NotebookController::class)
    ->prefix('notebooks')
    ->name('notebooks.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::match(['get', 'post'], '/edit', 'edit')->name('edit');
    });
