<?php

use App\Classes\Hook;

// Добавляем ссылку в меню сайта
Hook::add('sidebarMenu', static fn () => '<li>
        <a class="menu-item' . (request()->is('games*') ? ' active' : '') . '" href="/games">
            <i class="menu-icon fa-solid fa-dice"></i>
            <span class="menu-label">' . __('game::games.module') . '</span>
        </a>
    </li>');
