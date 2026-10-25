<?php

use Illuminate\Support\Facades\Route;
use Modules\Docs\Http\Controllers\DocsController;
use Modules\Docs\Http\Controllers\RotorController;

Route::middleware('web')->group(function () {
    Route::controller(RotorController::class)
        ->prefix('rotor')
        ->group(function () {
            Route::get('/', 'index')->name('rotor.index');
            Route::get('/releases', 'releases')->name('rotor.releases');
            Route::get('/releases/{tag}', 'release')->name('rotor.release');
            Route::get('/commits', 'commits')->name('rotor.commits');
            Route::get('/modules', 'modules')->name('rotor.modules');
            Route::get('/modules/{module}', 'module')->where('module', '[A-Za-z0-9_-]+')->name('rotor.module');
        });

    Route::controller(DocsController::class)
        ->prefix('docs')
        ->group(function () {
            Route::get('/find', 'search')->name('docs.search');
            Route::get('/{page?}', 'show')->name('docs.show');
        });
});
