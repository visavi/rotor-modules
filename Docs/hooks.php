<?php

use App\Classes\Hook;
use App\Services\GithubService;

// Добавляем ссылку в меню сайта
Hook::add('sidebarMenuEnd', function ($content) {
    $version = (new GithubService())->getLatestVersion();

    return $content . '<li>
        <a class="menu-item' . (request()->is('rotor*') ? ' active' : '') . '" href="/rotor">
            <i class="menu-icon fa-solid fa-gear"></i>
            <span class="menu-label text-danger">RotorCMS</span>
            <span class="badge menu-badge">' . $version . '</span>
        </a>
    </li>' . PHP_EOL;
});
