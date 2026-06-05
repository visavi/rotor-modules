<?php

use Illuminate\Support\Facades\Route;
use Modules\Load\Http\Controllers\Admin\LoadController as AdminLoadController;
use Modules\Load\Http\Controllers\Admin\LoadSettingController;
use Modules\Load\Http\Controllers\Load\ActiveController;
use Modules\Load\Http\Controllers\Load\DownController;
use Modules\Load\Http\Controllers\Load\LoadController;
use Modules\Load\Http\Controllers\Load\NewController;

/* Редиректы */
Route::redirect('/down', '/downs', 301);
Route::redirect('/down/{id}', '/downs/{id}', 301);
Route::redirect('/loads/search', '/search', 301);
Route::redirect('/loads/top', '/downs?sort=rating', 301);
Route::redirect('/downs/comments/{id}', '/downs/{id}', 301);
Route::redirect('/downs/comment/{id}/{cid}', '/downs/{id}?cid={cid}', 301);
Route::redirect('/downs/end/{id}', '/downs/{id}', 301);
Route::redirect('/downs/rss/{id}', '/downs/{id}', 301);
Route::redirect('/downs/{id}/rss', '/downs/{id}', 301);
Route::get('/downs/zip/{id}', [DownController::class, 'redirectZip']);
Route::get('/downs/zip/{id}/{fid}', [DownController::class, 'redirectZip']);

/* Категории загрузок */
Route::middleware('web')->group(function () {
    Route::prefix('loads')
        ->name('loads.')
        ->group(function () {
            Route::get('/', [LoadController::class, 'index'])->name('index');
            Route::get('/{id}', [LoadController::class, 'load'])->name('load');
            Route::get('/rss', [LoadController::class, 'rss'])->name('rss');
        });

    /* Загрузки */
    Route::prefix('downs')
        ->name('downs.')
        ->group(function () {
            Route::get('/', [NewController::class, 'files'])->name('new-files');
            Route::get('/comments', [NewController::class, 'comments'])->name('new-comments');

            Route::get('/active/files', [ActiveController::class, 'files'])->name('active-files');
            Route::get('/active/comments', [ActiveController::class, 'comments'])->name('active-comments');

            Route::get('/{id}', [DownController::class, 'view'])->name('view');
            Route::post('/{id}/comments', [DownController::class, 'storeComment'])->name('add-comment');

            Route::get('/{id}/download/{fid}', [DownController::class, 'download'])->name('download');
            Route::get('/{id}/link/{lid}', [DownController::class, 'downloadLink'])->whereNumber('lid')->name('download-link');

            Route::get('/{id}/zip/{fid}', [DownController::class, 'zip'])->name('zip');
            Route::get('/{id}/zip/{fid}/{zid}', [DownController::class, 'zipView'])->whereNumber('zid')->name('zip-view');

            Route::match(['get', 'post'], '/create', [DownController::class, 'create'])->name('create');
            Route::match(['get', 'post'], '/{id}/edit', [DownController::class, 'edit'])->name('edit');
        });
});

/* Административная панель */
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        /* Загрузки */
        Route::controller(AdminLoadController::class)
            ->prefix('loads')
            ->name('admin.loads.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/create', 'create')->name('create');
                Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
                Route::delete('/{id}/delete', 'delete')->name('delete');
                Route::get('/{id}', 'load')->name('load');
                Route::post('/restatement', 'restatement')->name('restatement');
            });

        Route::controller(AdminLoadController::class)
            ->prefix('downs')
            ->name('admin.downs.')
            ->group(function () {
                Route::match(['get', 'post'], '/{id}/edit', 'editDown')->name('edit');
                Route::get('/new', 'new')->name('new');
                Route::post('/{id}/publish', 'publish')->name('publish');
                Route::delete('/delete/{id}', 'deleteDown')->name('delete');
            });

        /* Настройки */
        Route::get('/load-settings', [LoadSettingController::class, 'index'])->name('load.settings');
        Route::post('/load-settings', [LoadSettingController::class, 'update'])->name('load.settings.update');
    });
