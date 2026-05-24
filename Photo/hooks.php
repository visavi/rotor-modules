<?php

use App\Classes\Hook;

// Ссылки на фото пользователя в анкете
Hook::add('userProfileLinks', static function ($user) {
    return '<li class="list-inline-item"><b><a href="' . route('photos.user-albums', ['user' => $user->login]) . '">' . __('index.photos') . '</a></b>'
        . ' (<a href="' . route('photos.user-comments', ['user' => $user->login]) . '">' . __('main.comments') . '</a>)</li>';
});

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    $url = route('photos.index');
    $active = request()->is('photos*') ? ' active' : '';
    $label = __('index.photos');
    $stats = statsPhotos();

    return '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon far fa-image"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>';
}, 10);

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', static function () {
    $url = route('admin.photos.index');
    $label = __('index.photos');
    $stats = statsPhotos();

    return '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('photo.settings') . '">' . __('photo::photos.settings') . '</a>');
