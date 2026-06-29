<?php

use App\Classes\Hook;

Hook::add('navbarStart', static function () {
    $user = getUser();

    if (! $user || ! $user->isActive()) {
        return null;
    }

    if (strtotime(date('d.m.Y')) > strtotime(now()->addDays(3)->format('03.01.Y'))) {
        return null;
    }

    return '<li><a class="app-nav__item" href="' . route('surprise') . '" aria-label="' . __('surprise::surprise.title') . '"><i class="fa-solid fa-gift fa-lg text-danger"></i></a></li>';
}, 10);
