<?php

use Illuminate\Support\Facades\Route;
use Modules\Notebook\Http\Controllers\Api\NotebookApiController;
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

/* ---- API роуты ---- */
// Заметка личная, читать и править её может только владелец
Route::middleware(['api', 'check.token'])
    ->prefix('api')
    ->group(function () {
        Route::get('/notebook', [NotebookApiController::class, 'show']);
        Route::patch('/notebook', [NotebookApiController::class, 'update']);
    });
