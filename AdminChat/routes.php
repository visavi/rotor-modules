<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminChat\Http\Controllers\Admin\ChatController;

Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(ChatController::class)
            ->prefix('chats')
            ->name('admin.chats.')
            ->group(function () {
                Route::match(['get', 'post'], '/', 'index')->name('index');
                Route::match(['get', 'post'], '/edit/{id}', 'edit')->name('edit');
                Route::post('/clear', 'clear')->name('clear');
            });
    });
