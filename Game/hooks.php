<?php

use App\Classes\Hook;

// Добавляем ссылку в меню сайта
Hook::add('sidebarMenuEnd', function ($content) {
    return $content . '<li>
        <a class="app-menu__item' . (request()->is('games*') ? ' active' : '') . '" href="/games">
            <i class="app-menu__icon fa-solid fa-dice"></i>
            <span class="app-menu__label">' . __('game::games.module') . '</span>
        </a>
    </li>' . PHP_EOL;
});
