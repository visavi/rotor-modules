<?php

use Illuminate\Support\Facades\Route;
use Modules\Rating\Http\Controllers\Admin\RatingSettingController;
use Modules\Rating\Http\Controllers\RatingController;

/* Репутация пользователя */
Route::middleware(['web', 'check.user'])
    ->controller(RatingController::class)
    ->group(function () {
        Route::prefix('ratings')
            ->group(function () {
                Route::get('/{login}/gave', 'gave')->name('ratings.gave');
                Route::get('/{login}/{received?}', 'received')->name('ratings.received');
                Route::post('/delete', 'delete')->name('ratings.delete');
            });

        Route::get('/users/{login}/rating', 'index')->name('users.rating');
        Route::post('/users/{login}/rating', 'store');
    });

/* Админ — настройки */
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->controller(RatingSettingController::class)
    ->prefix('admin')
    ->name('rating.')
    ->group(function () {
        Route::get('/rating-settings', 'index')->name('settings');
        Route::post('/rating-settings', 'update')->name('settings.update');
    });
