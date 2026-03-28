<?php

use Illuminate\Support\Facades\Route;
use Modules\Gift\Controllers\IndexController;
use Modules\Gift\Controllers\PanelController;

/* Подарки */
Route::middleware('web')
    ->prefix('gifts')
    ->group(function () {
        Route::get('/', [IndexController::class, 'index']);
        Route::match(['get', 'post'], '/send/{id}', [IndexController::class, 'send']);
        Route::get('/{login}', [IndexController::class, 'gifts']);
    });

Route::middleware('web')
    ->prefix('admin')
    ->group(function () {
        Route::match(['get', 'post'], '/gifts', [PanelController::class, 'index']);
        Route::delete('/gifts/{id}', [PanelController::class, 'delete']);
    });
