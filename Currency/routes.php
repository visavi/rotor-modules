<?php

use Illuminate\Support\Facades\Route;
use Modules\Currency\Http\Controllers\Api\CurrencyApiController;

/* ---- API роуты ---- */
// Курсы публичны, токен не нужен
Route::middleware(['api', 'check.token.optional'])
    ->prefix('api')
    ->group(function () {
        Route::get('/currency', [CurrencyApiController::class, 'index']);
    });
