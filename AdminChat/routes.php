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
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store');
                Route::get('/edit/{id}', 'edit')->name('edit');
                Route::post('/edit/{id}', 'update');
                Route::post('/clear', 'clear')->name('clear')->middleware('check.admin:boss');
            });
    });
