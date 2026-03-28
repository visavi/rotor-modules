<?php

use Illuminate\Support\Facades\Route;
use Modules\UserLocation\Controllers\UserLocationController;

/*  Кто-где */
Route::middleware('web')
    ->prefix('locations')
    ->name('locations.')
    ->group(function () {
        Route::get('/', [UserLocationController::class, 'index'])->name('index');
    });
