<?php

use Illuminate\Support\Facades\Route;

Route::get('/recent', fn () => view('classic::recent'))->name('classic.recent');
