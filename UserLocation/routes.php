<?php

use Illuminate\Support\Facades\Route;

/*  Кто-где */
Route::middleware('web')
    ->prefix('locations')
    ->group(function () {
        Route::get('/', [\Modules\UserLocation\Controllers\UserLocationController::class, 'index']);
    });
