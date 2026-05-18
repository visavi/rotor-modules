<?php

use Illuminate\Support\Facades\Route;
use Modules\Surprise\Controllers\SurpriseController;

Route::get('/surprise', [SurpriseController::class, 'index'])->name('surprise');
