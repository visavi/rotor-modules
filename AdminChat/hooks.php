<?php

use App\Classes\Hook;

// Плитка в панели редактора
Hook::add('adminBlockEditor', static function () {
    return '<div class="col">
        <a href="' . route('admin.chats.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#0dcaf0"><i class="fas fa-comments"></i></div>
            <div class="app-tile-label">' . __('admin_chat::admin_chat.admin_chat') . '<span class="badge bg-adaptive app-tile-badge">' . statsChat() . '</span></div>
        </a>
    </div>';
});

// Индикатор новых сообщений в навбаре
Hook::add('navbarStart', static function () {
    $user = getUser();

    if (! $user || ! isAdmin() || $user->newchat >= statsNewChat()) {
        return '';
    }

    return '<li>
        <a class="app-nav__item" href="' . route('admin.chats.index') . '" aria-label="' . __('admin_chat::admin_chat.chat') . '">
            <i class="far fa-bell fa-lg"></i>
            <span class="badge bg-notify">!</span>
        </a>
    </li>';
});
