<?php

use Illuminate\Support\Facades\Route;
use Modules\Photo\Controllers\Admin\PhotoController as AdminPhotoController;
use Modules\Photo\Controllers\Admin\PhotoSettingController;
use Modules\Photo\Controllers\PhotoController;

/* Галерея */
Route::middleware('web')
    ->controller(PhotoController::class)
    ->prefix('photos')
    ->name('photos.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'view')->name('view');
        Route::post('/{id}/comments', 'storeComment')->name('add-comment');
        Route::delete('/{id}/delete', 'delete')->name('delete');
        Route::get('/albums', 'albums')->name('albums');
        Route::get('/comments', 'allComments')->name('all-comments');
        Route::get('/active/albums', 'album')->name('user-albums');
        Route::get('/active/comments', 'userComments')->name('user-comments');
        Route::match(['get', 'post'], '/create', 'create')->name('create');
        Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
    });

/* Админ — галерея */
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(AdminPhotoController::class)
            ->prefix('photos')
            ->name('admin.photos.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
                Route::delete('/{id}/delete', 'delete')->name('delete');
                Route::post('/restatement', 'restatement')->name('restatement');
            });

        Route::controller(PhotoSettingController::class)
            ->name('photo.')
            ->group(function () {
                Route::get('/photo-settings', 'index')->name('settings');
                Route::post('/photo-settings', 'update')->name('settings.update');
            });
    });
