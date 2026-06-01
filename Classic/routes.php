<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/recent', fn () => view('classic::recent'))->name('classic.recent');
});
