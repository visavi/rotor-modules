<?php

use Illuminate\Support\Facades\Route;
use Modules\Delivery\Http\Controllers\Admin\DeliveryController;

/* Приват-рассылка */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/delivery', [DeliveryController::class, 'index'])->name('admin.delivery.index');
        Route::post('/delivery', [DeliveryController::class, 'send']);
    });
