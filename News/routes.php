<?php

use Illuminate\Support\Facades\Route;
use Modules\News\Http\Controllers\Admin\NewsController as AdminNewsController;
use Modules\News\Http\Controllers\Admin\NewsSettingController;
use Modules\News\Http\Controllers\NewsController;

/* Редиректы */
Route::redirect('/news/comments/{id}', '/news/{id}', 301);
Route::redirect('/news/comment/{id}/{cid}', '/news/{id}?cid={cid}', 301);
Route::redirect('/news/end/{id}', '/news/{id}', 301);
Route::redirect('/news/{id}/comments', '/news/{id}', 301);

/* Новости */
Route::middleware('web')
    ->controller(NewsController::class)
    ->prefix('news')
    ->name('news.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/rss', 'rss')->name('rss');
        Route::get('/allcomments', 'allComments')->name('all-comments');
        Route::get('/{id}', 'view')->name('view');
        Route::post('/{id}/comments', 'storeComment')->name('add-comment');
    });

/* Админ */
Route::middleware(['web', 'check.admin:admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(AdminNewsController::class)
            ->prefix('news')
            ->name('admin.news.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::match(['get', 'post'], '/create', 'create')->name('create');
                Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
                Route::delete('/{id}/delete', 'delete')->name('delete');
                Route::post('/restatement', 'restatement')->name('restatement');
            });

        /* Настройки */
        Route::controller(NewsSettingController::class)
            ->name('news.')
            ->group(function () {
                Route::get('/news-settings', 'index')->name('settings');
                Route::post('/news-settings', 'update')->name('settings.update');
            });
    });
