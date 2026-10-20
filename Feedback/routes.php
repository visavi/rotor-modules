<?php

use Illuminate\Support\Facades\Route;
use Modules\Feedback\Http\Controllers\Admin\FeedbackSettingController;
use Modules\Feedback\Http\Controllers\FeedbackController;

Route::middleware('web')
    ->controller(FeedbackController::class)
    ->name('feedback.')
    ->group(function () {
        Route::get('/feedback', 'index')->name('index');
        Route::post('/feedback', 'send')->name('send');
    });

/* Админ — настройки */
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->controller(FeedbackSettingController::class)
    ->prefix('admin')
    ->name('feedback.')
    ->group(function () {
        Route::get('/feedback-settings', 'index')->name('settings');
        Route::post('/feedback-settings', 'store')->name('settings.update');
    });
