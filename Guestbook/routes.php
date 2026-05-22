<?php

use Illuminate\Support\Facades\Route;
use Modules\Guestbook\Http\Controllers\Admin\GuestbookController as AdminGuestbookController;
use Modules\Guestbook\Http\Controllers\Admin\GuestbookSettingController;
use Modules\Guestbook\Http\Controllers\GuestbookController;

Route::middleware('web')
    ->controller(GuestbookController::class)
    ->prefix('guestbook')
    ->name('guestbook.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/create', 'add')->name('create');
        Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
    });

Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(AdminGuestbookController::class)
            ->prefix('guestbook')
            ->name('admin.guestbook.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
                Route::match(['get', 'post'], '/{id}/reply', 'reply')->name('reply');
                Route::post('/delete', 'delete')->name('delete');
                Route::post('/publish', 'publish')->name('publish');
                Route::post('/clear', 'clear')->name('clear');
            });

        Route::controller(GuestbookSettingController::class)
            ->name('guestbook.')
            ->group(function () {
                Route::get('/guestbook-settings', 'index')->name('settings');
                Route::post('/guestbook-settings', 'update')->name('settings.update');
            });
    });
