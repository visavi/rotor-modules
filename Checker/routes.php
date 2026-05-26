<?php

use Illuminate\Support\Facades\Route;
use Modules\Checker\Http\Controllers\Admin\CheckerController;

Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(CheckerController::class)
            ->prefix('checkers')
            ->group(function () {
                Route::match(['get', 'post'], '/', 'index');
                Route::post('/scan', 'scan');
            });
    });
