<?php

use App\Classes\Hook;
use Modules\Gift\Models\GiftsUser;

// Добавляем ссылку на подарки в меню сайта
Hook::add('sidebarMenu', static fn () => '<li>
        <a class="menu-item' . (request()->is('gifts*') ? ' active' : '') . '" href="/gifts">
            <i class="menu-icon fas fa-gift"></i>
            <span class="menu-label">Подарки</span>
        </a>
    </li>');

// Добавляем ссылку на мои подарки в личный кабинет
Hook::add('userActionMiddle', static function ($user) {
    $giftsCount = GiftsUser::query()->where('user_id', $user->id)->count();

    return '<i class="fas fa-gift"></i> <a href="/gifts/' . $user->login . '">Подарки</a> (' . $giftsCount . ')<br>';
});

// Добавляем ссылку на отправку подарка пользователю
Hook::add('userNotPersonalStart', static fn ($user) => '<i class="fas fa-gift"></i> <a href="/gifts?user=' . $user->login . '">Отправить подарок</a><br>');
