<?php

use Illuminate\Support\Facades\Route;
use Modules\Privacy\Http\Controllers\Admin\PrivacyTextController;
use Modules\Privacy\Http\Controllers\PrivacyController;

Route::middleware('web')->group(function () {
    Route::get('/privacy', [PrivacyController::class, 'index'])->name('privacy.index');
});

Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin/privacy')
    ->controller(PrivacyTextController::class)
    ->name('privacy.admin.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show')->name('show');
    });
