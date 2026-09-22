<?php

use Illuminate\Support\Facades\Route;
use Modules\UserField\Http\Controllers\Admin\UserFieldController;

/* Админ — пользовательские поля (только для boss, как и в ядре ранее) */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        // До ресурсного маршрута: иначе /sort попал бы в update по {id}
        Route::post('user-fields/sort', [UserFieldController::class, 'sort'])->name('user-fields.sort');

        Route::resource('user-fields', UserFieldController::class)
            ->parameters(['user-fields' => 'id'])
            ->except('show');
    });
