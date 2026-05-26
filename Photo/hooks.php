<?php

use App\Classes\Hook;

// Ссылки на фото пользователя в анкете
Hook::add('userProfileLinks', static function ($user) {
    return '<li class="list-inline-item"><b><a href="' . route('photos.user-albums', ['user' => $user->login]) . '">' . __('photo::photos.photos') . '</a></b>'
        . ' (<a href="' . route('photos.user-comments', ['user' => $user->login]) . '">' . __('main.comments') . '</a>)</li>';
});

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    $url = route('photos.index');
    $active = request()->is('photos*') ? ' active' : '';
    $label = __('photo::photos.photos');
    $stats = statsPhotos();

    return '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon far fa-image"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>';
}, 14);

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', static function () {
    return '<div class="col">
        <a href="' . route('admin.photos.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#e91e63"><i class="far fa-image"></i></div>
            <div class="app-tile-label">' . __('photo::photos.photos') . '<span class="badge bg-adaptive app-tile-badge">' . statsPhotos() . '</span></div>
        </a>
    </div>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('photo.settings') . '">' . __('photo::photos.settings') . '</a>');
