<?php

use Illuminate\Support\Facades\Route;

/* Подарки */
Route::group(['prefix' => 'gifts'], function () {
    Route::get('/', [\Modules\Gift\Controllers\IndexController::class, 'index']);
    Route::match(['get', 'post'], '/send/{id:\d+}', [\Modules\Gift\Controllers\IndexController::class, 'send']);
    Route::get('/{login:[\w\-]+}', [\Modules\Gift\Controllers\IndexController::class, 'gifts']);
});

Route::group(['prefix' => 'admin'], function () {
    Route::match(['get', 'post'], '/gifts', [\Modules\Gift\Controllers\PanelController::class, 'index']);
    Route::get('/gifts/delete', [\Modules\Gift\Controllers\PanelController::class, 'delete']);
});
