<?php

use Illuminate\Support\Facades\Route;
use Modules\Delivery\Http\Controllers\Admin\DeliveryController;

/* Приват-рассылка */
Route::middleware(['web', 'check.admin', 'admin.logger', 'check.admin:boss'])
    ->prefix('admin')
    ->group(function () {
        Route::match(['get', 'post'], '/delivery', [DeliveryController::class, 'index']);
    });
