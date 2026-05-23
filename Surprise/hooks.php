<?php

use App\Classes\Hook;

Hook::add('navbarSurprise', static function () {
    if (strtotime(date('d.m.Y')) > strtotime(date('03.01.Y', strtotime('+3 days', SITETIME)))) {
        return null;
    }

    return '<li><a class="app-nav__item" href="' . route('surprise') . '" aria-label="' . __('surprise::surprise.title') . '"><i class="fa-solid fa-gift fa-lg text-danger"></i></a></li>';
}, 10);
