<?php

use Illuminate\Support\Facades\Route;
use Modules\Board\Controllers\Admin\BoardController as AdminBoardController;
use Modules\Board\Controllers\Admin\BoardSettingController;
use Modules\Board\Controllers\BoardController;

Route::pattern('id', '\d+');

/* Категории объявлений */
Route::middleware('web')
    ->controller(BoardController::class)
    ->prefix('boards')
    ->name('boards.')
    ->group(function () {
        Route::get('/{id?}', 'index')->name('index');
        Route::get('/active', 'active')->name('active');
    });

/* Объявления */
Route::middleware('web')
    ->controller(BoardController::class)
    ->prefix('items')
    ->name('items.')
    ->group(function () {
        Route::get('/{id}', 'view')->name('view');
        Route::post('/{id}/close', 'close')->name('close');
        Route::delete('/{id}/delete', 'delete')->name('delete');
        Route::match(['get', 'post'], '/create', 'create')->name('create');
        Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
    });

/* Админ — категории и объявления */
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(AdminBoardController::class)
            ->prefix('boards')
            ->name('admin.boards.')
            ->group(function () {
                Route::get('/{id?}', 'index')->name('index');
                Route::get('/categories', 'categories')->name('categories');
                Route::post('/create', 'create')->name('create');
                Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
                Route::delete('/{id}/delete', 'delete')->name('delete');
                Route::post('/restatement', 'restatement')->name('restatement');
            });

        Route::controller(AdminBoardController::class)
            ->prefix('items')
            ->name('admin.items.')
            ->group(function () {
                Route::match(['get', 'post'], '/{id}/edit', 'editItem')->name('edit');
                Route::delete('/{id}/delete', 'deleteItem')->name('delete');
            });

        /* Настройки */
        Route::controller(BoardSettingController::class)
            ->name('board.')
            ->group(function () {
                Route::get('/board-settings', 'index')->name('settings');
                Route::post('/board-settings', 'update')->name('settings.update');
            });
    });
