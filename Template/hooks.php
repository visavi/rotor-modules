<?php

use App\Classes\Hook;

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    return '<li>
        <a class="menu-item' . (request()->is('template*') ? ' active' : '') . '" href="' . route('template.index') . '">
            <i class="menu-icon far fa-file"></i>
            <span class="menu-label">' . __('template::template.template') . '</span>
            <span class="badge menu-badge">' . statsTemplate() . '</span>
        </a>
    </li>';
}, 10);

// Ссылка в блоке модератора
Hook::add('adminBlockModer', static function () {
    return '<div class="col">
        <a href="' . route('admin.template.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#fd7e14"><i class="far fa-file"></i></div>
            <div class="app-tile-label">' . __('template::template.template') . '<span class="badge bg-adaptive app-tile-badge">' . statsTemplate() . '</span></div>
        </a>
    </div>';
});
