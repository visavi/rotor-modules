<?php

use Illuminate\Support\Facades\Route;
use Modules\Delivery\Http\Controllers\Admin\DeliveryController;

/* Приват-рассылка */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::match(['get', 'post'], '/delivery', [DeliveryController::class, 'index']);
    });
