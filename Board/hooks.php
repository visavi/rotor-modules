<?php

use App\Classes\Hook;

// Ссылка в боковом меню
Hook::add('sidebarMenuEnd', static function () {
    $url = route('boards.index');
    $active = request()->is('boards*', 'item*') ? ' active' : '';
    $label = __('index.boards');
    $stats = statsBoard();

    return '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon far fa-rectangle-list"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>';
}, 10);

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static function () {
    return '<a class="nav-link" href="' . route('board.settings') . '">' . __('board::boards.settings') . '</a>';
});

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', static function () {
    $url = route('admin.boards.index');
    $label = __('index.boards');
    $stats = statsBoard();

    return '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>';
});
