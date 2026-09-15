<?php

use App\Http\Middleware\CheckUserState;
use App\Http\Middleware\SaveStatistic;
use Illuminate\Support\Facades\Route;
use Modules\Notifier\Http\Controllers\Admin\NotifierSettingController;
use Modules\Notifier\Http\Controllers\NotifierController;

/*
 * Фоновая проверка новых сообщений
 *
 * SaveStatistic и CheckUserState отключены намеренно: опрос идёт без участия
 * человека и не должен поднимать пользователя в онлайн, обновлять его визит
 * и крутить счётчики хостов и хитов. Забаненный при этом получает честный
 * auth: false и прекращает опрос, а не редирект на страницу бана
 */
Route::middleware('web')
    ->withoutMiddleware([SaveStatistic::class, CheckUserState::class])
    ->get('/notifier/check', [NotifierController::class, 'check'])
    ->name('notifier.check');

/* Админ — настройки */
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->controller(NotifierSettingController::class)
    ->prefix('admin')
    ->name('notifier.')
    ->group(function () {
        Route::get('/notifier-settings', 'index')->name('settings');
        Route::post('/notifier-settings', 'update')->name('settings.update');
    });
