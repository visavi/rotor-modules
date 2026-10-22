<?php

use Illuminate\Support\Facades\Route;
use Modules\Board\Http\Controllers\Admin\BoardController as AdminBoardController;
use Modules\Board\Http\Controllers\Admin\BoardSettingController;
use Modules\Board\Http\Controllers\Api\ItemApiController;
use Modules\Board\Http\Controllers\BoardController;

/* Категории объявлений */
Route::middleware('web')
    ->controller(BoardController::class)
    ->prefix('boards')
    ->name('boards.')
    ->group(function () {
        Route::get('/{id?}', 'index')->name('index');
        Route::get('/cities', 'cities')->middleware('throttle:60,1')->name('cities');
        Route::get('/active', 'active')->name('active')->middleware('check.user');
    });

/* Объявления */
Route::middleware('web')
    ->controller(BoardController::class)
    ->prefix('items')
    ->name('items.')
    ->group(function () {
        Route::get('/{id}', 'view')->name('view');
        // Лимит против перебора объявлений подряд
        Route::post('/{id}/phone', 'phone')->middleware('throttle:20,1')->name('phone');

        Route::middleware('check.user')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/create', 'store');
            Route::get('/{id}/edit', 'edit')->name('edit');
            Route::post('/{id}/edit', 'update');
            Route::post('/{id}/close', 'close')->name('close');
            Route::delete('/{id}/delete', 'delete')->name('delete');
        });
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

                // Категории и пересчёт — только boss
                Route::middleware('check.admin:boss')->group(function () {
                    Route::get('/categories', 'categories')->name('categories');
                    Route::post('/sort', 'sort')->name('sort');
                    Route::post('/create', 'create')->name('create');
                    Route::get('/{id}/edit', 'edit')->name('edit');
                    Route::post('/{id}/edit', 'update');
                    Route::delete('/{id}/delete', 'delete')->name('delete');
                    Route::post('/restatement', 'restatement')->name('restatement');
                });
            });

        Route::controller(AdminBoardController::class)
            ->prefix('items')
            ->name('admin.items.')
            ->group(function () {
                Route::get('/{id}/edit', 'editItem')->name('edit');
                Route::post('/{id}/edit', 'updateItem');
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

/* ---- API роуты ---- */
// Токен необязателен: объявления читают и гости
Route::middleware(['api', 'check.token.optional'])
    ->prefix('api')
    ->group(function () {
        Route::get('/items', [ItemApiController::class, 'index']);
        Route::get('/items/{id}', [ItemApiController::class, 'view']);
        Route::get('/boards', [ItemApiController::class, 'categories']);
    });

// Создание, правка и снятие — только с токеном
Route::middleware(['api', 'check.token'])
    ->prefix('api')
    ->group(function () {
        Route::post('/items', [ItemApiController::class, 'store']);
        Route::patch('/items/{id}', [ItemApiController::class, 'update']);
        Route::post('/items/{id}/publish', [ItemApiController::class, 'publish']);
        Route::delete('/items/{id}', [ItemApiController::class, 'destroy']);
    });
