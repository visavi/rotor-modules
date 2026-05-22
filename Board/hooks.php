<?php

use App\Classes\Hook;

// Секция на классической главной
Hook::add('classicSections', function (string $content) {
    $url = route('boards.index');
    $label = __('index.boards');
    $stats = statsBoard();
    $items = recentBoards();

    return $content . '<div class="section mb-3 shadow">
    <div class="section-title">
        <i class="fa fa-list-alt fa-lg text-muted"></i>
        <a href="' . $url . '">' . $label . '</a>
        <span class="badge bg-adaptive">' . $stats . '</span>
    </div>
    ' . $items . '
</div>' . PHP_EOL;
});

// Ссылка в боковом меню
Hook::add('sidebarMenuEnd', function (string $content) {
    $url = route('boards.index');
    $active = request()->is('boards*', 'item*') ? ' active' : '';
    $label = __('index.boards');
    $stats = statsBoard();

    return $content . '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon far fa-rectangle-list"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>' . PHP_EOL;
}, 10);

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', function (string $content) {
    $url = route('board.settings');
    $label = __('board::boards.settings');

    return $content . '<a class="nav-link" href="' . $url . '">' . $label . '</a>' . PHP_EOL;
});

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', function (string $content) {
    $url = route('admin.boards.index');
    $label = __('index.boards');
    $stats = statsBoard();

    return $content . '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>' . PHP_EOL;
});
