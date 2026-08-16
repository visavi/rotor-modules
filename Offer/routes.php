<?php

use Illuminate\Support\Facades\Route;
use Modules\Offer\Http\Controllers\Admin\OfferController as AdminOfferController;
use Modules\Offer\Http\Controllers\Admin\OfferSettingController;
use Modules\Offer\Http\Controllers\Api\OfferApiController;
use Modules\Offer\Http\Controllers\OfferController;

/* Редиректы */
Route::redirect('/offers/comments/{id}', '/offers/{id}', 301);
Route::redirect('/offers/comment/{id}/{cid}', '/offers/{id}?cid={cid}', 301);
Route::redirect('/offers/end/{id}', '/offers/{id}', 301);

/* Предложения и проблемы */
Route::middleware('web')
    ->controller(OfferController::class)
    ->prefix('offers')
    ->name('offers.')
    ->group(function () {
        Route::get('/{type?}', 'index')->where('type', 'offer|issue')->name('index');
        Route::get('/{id}', 'view')->name('view');
        Route::post('/{id}/comments', 'storeComment')->name('add-comment');
        Route::match(['get', 'post'], '/create', 'create')->name('create');
        Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
    });

/* Админ */
Route::middleware(['web', 'check.admin:admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(AdminOfferController::class)
            ->prefix('offers')
            ->name('admin.offers.')
            ->group(function () {
                Route::get('/{type?}', 'index')->where('type', 'offer|issue')->name('index');
                Route::get('/{id}', 'view')->name('view');
                Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
                Route::match(['get', 'post'], '/{id}/reply', 'reply')->name('reply');
                Route::match(['get', 'post'], '/delete', 'delete')->name('delete');
                Route::post('/restatement', 'restatement')->name('restatement');
            });

        /* Настройки */
        Route::controller(OfferSettingController::class)
            ->name('offer.')
            ->group(function () {
                Route::get('/offer-settings', 'index')->name('settings');
                Route::post('/offer-settings', 'update')->name('settings.update');
            });
    });

/* ---- API роуты ---- */
// Токен необязателен: гость читает запись, авторизованный получает ещё и свой голос
Route::middleware(['api', 'check.token.optional'])
    ->prefix('api')
    ->group(function () {
        Route::get('/offers', [OfferApiController::class, 'index']);
        Route::get('/offers/{id}', [OfferApiController::class, 'view']);
    });

// Создание и правка — только с токеном
Route::middleware(['api', 'check.token'])
    ->prefix('api')
    ->group(function () {
        Route::post('/offers', [OfferApiController::class, 'store']);
        Route::patch('/offers/{id}', [OfferApiController::class, 'update']);
    });
