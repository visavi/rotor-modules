<?php

use App\Classes\Hook;
use App\Services\GithubService;

// Добавляем ссылку в меню сайта
Hook::add('sidebarMenuEnd', function ($content) {
    $version = (new GithubService())->getLatestVersion();

    return $content . '<li>
        <a class="app-menu__item' . (request()->is('rotor*') ? ' active' : '') . '" href="/rotor">
            <i class="app-menu__icon fa-solid fa-gear"></i>
            <span class="app-menu__label text-danger">RotorCMS</span>
            <span class="badge bg-dark bg-gradient">' . $version . '</span>
        </a>
    </li>' . PHP_EOL;
});
