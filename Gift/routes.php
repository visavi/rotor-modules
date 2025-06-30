<?php

use Illuminate\Support\Facades\Route;

/* Подарки */
Route::middleware('web')
    ->prefix('gifts')
    ->group(function () {
        Route::get('/', [\Modules\Gift\Controllers\IndexController::class, 'index']);
        Route::match(['get', 'post'], '/send/{id}', [\Modules\Gift\Controllers\IndexController::class, 'send']);
        Route::get('/{login}', [\Modules\Gift\Controllers\IndexController::class, 'gifts']);
    });

Route::middleware('web')
    ->prefix('admin')
    ->group(function () {
        Route::match(['get', 'post'], '/gifts', [\Modules\Gift\Controllers\PanelController::class, 'index']);
        Route::get('/gifts/delete', [\Modules\Gift\Controllers\PanelController::class, 'delete']);
    });
