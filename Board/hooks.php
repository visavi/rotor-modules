<?php

use App\Classes\Hook;

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    $url = route('boards.index');
    $active = request()->is('boards*', 'item*') ? ' active' : '';
    $label = __('board::boards.boards');
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
    return '<div class="col">
        <a href="' . route('admin.boards.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#fd7e14"><i class="far fa-rectangle-list"></i></div>
            <div class="app-tile-label">' . __('board::boards.boards') . '<span class="badge bg-adaptive app-tile-badge">' . statsBoard() . '</span></div>
        </a>
    </div>';
});
