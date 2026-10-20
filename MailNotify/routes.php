<?php

use Illuminate\Support\Facades\Route;
use Modules\MailNotify\Http\Controllers\Admin\MailNotifySettingController;
use Modules\MailNotify\Http\Controllers\UnsubscribeController;

// Адрес из писем и заголовка List-Unsubscribe (MailService ядра) — не менять
Route::middleware('web')
    ->match(['get', 'post'], '/unsubscribe', UnsubscribeController::class)
    ->name('mail-notify.unsubscribe');

Route::middleware(['web', 'check.admin:boss', 'admin.logger'])
    ->prefix('admin')
    ->controller(MailNotifySettingController::class)
    ->name('mail-notify.')
    ->group(function () {
        Route::get('/mail-notify-settings', 'index')->name('settings');
        Route::post('/mail-notify-settings', 'store')->name('settings.update');
    });
