<?php

use Illuminate\Support\Facades\Route;
use Modules\PageEditor\Http\Controllers\Admin\FileController;
use Modules\PageEditor\Http\Controllers\Admin\SearchController;
use Modules\PageEditor\Http\Controllers\Admin\TranslationController;

/* Редактор страниц */
Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(FileController::class)
            ->prefix('files')
            ->name('admin.files.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::match(['get', 'post'], '/edit', 'edit')->name('edit');
                Route::match(['get', 'post'], '/create', 'create')->name('create');
                Route::delete('/delete', 'delete')->name('delete');
                Route::post('/rename', 'rename')->name('rename');
                Route::post('/upload', 'upload')->name('upload');
                Route::get('/download', 'download')->name('download');
            });

        Route::get('/files/search', [SearchController::class, 'index'])->name('admin.files.search');
        Route::get('/files/translations', [TranslationController::class, 'index'])->name('admin.files.translations');
        Route::post('/files/translations', [TranslationController::class, 'save'])->name('admin.files.translations.save');
    });
