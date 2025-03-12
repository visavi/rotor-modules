<?php

use App\Classes\Hook;
use Modules\Gift\Models\GiftsUser;

// Добавляем ссылку на подарки в меню сайта
Hook::add('sidebarMenuEnd', function ($content) {
    return $content . '<li>
        <a class="app-menu__item' . (request()->is('gifts*') ? ' active' : '') . '" href="/gifts">
            <i class="app-menu__icon fas fa-gift"></i>
            <span class="app-menu__label">Подарки</span>
        </a>
    </li>' . PHP_EOL;
});

// Добавляем ссылку на мои подарки в личный кабинет
Hook::add('userPersonalEnd', function ($content) {
    $giftsCount = GiftsUser::query()->where('user_id', getUser('id'))->count();

    return $content . '<i class="fas fa-gift"></i> <a href="/gifts/' . getUser('login') . '">Мои подарки</a> (' . $giftsCount . ')<br>' . PHP_EOL;
});

// Добавляем ссылку на отправку подарка пользователю
Hook::add('userNotPersonalEnd', function ($content, $user) {
    return $content . '<i class="fas fa-gift"></i> <a href="/gifts?user=' . $user->login . '">Отправить подарок</a><br>' . PHP_EOL;
});
