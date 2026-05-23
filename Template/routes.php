<?php

use Illuminate\Support\Facades\Route;
use Modules\Template\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use Modules\Template\Http\Controllers\TemplateController;

Route::middleware('web')
    ->controller(TemplateController::class)
    ->prefix('template')
    ->name('template.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/create', 'store')->middleware('auth')->name('store');
    });

Route::admin()
    ->controller(AdminTemplateController::class)
    ->prefix('template')
    ->name('admin.template.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/delete', 'delete')->name('delete');
    });
