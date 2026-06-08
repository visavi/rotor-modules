<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SocialAuth\Http\Controllers\Admin\SocialAuthSettingController;
use Modules\SocialAuth\Http\Controllers\SocialAuthController;

Route::middleware('web')
    ->controller(SocialAuthController::class)
    ->prefix('auth')
    ->group(function () {
        Route::get('/{provider}/redirect', 'redirect')->name('social.redirect')->where('provider', '[a-z]+');
        Route::get('/{provider}/callback', 'callback')->name('social.callback')->where('provider', '[a-z]+');
    });

Route::middleware('web')
    ->controller(SocialAuthController::class)
    ->prefix('social')
    ->group(function () {
        Route::get('/accounts', 'accounts')->name('social.accounts');
        Route::get('/complete', 'completeForm')->name('social.complete');
        Route::post('/complete', 'complete')->name('social.complete.post');
        Route::get('/{provider}/link', 'link')->name('social.link')->where('provider', '[a-z]+');
        Route::delete('/{provider}/unlink', 'unlink')->name('social.unlink')->where('provider', '[a-z]+');
    });

Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->controller(SocialAuthSettingController::class)
    ->prefix('admin')
    ->group(function () {
        Route::get('/social-auth-settings', 'index')->name('social_auth.settings');
        Route::post('/social-auth-settings', 'update')->name('social_auth.settings.update');
    });
