<?php

use Illuminate\Support\Facades\Route;
use Modules\Surprise\Http\Controllers\SurpriseController;

Route::middleware('web')->group(function () {
    Route::get('/surprise', [SurpriseController::class, 'index'])->name('surprise');
});
