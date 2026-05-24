<?php

use App\Classes\Hook;

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    $url = route('template.index');
    $active = request()->is('template*') ? ' active' : '';
    $label = __('template::template.template');
    $stats = statsTemplate();

    return '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon far fa-file"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>';
}, 10);

// Ссылка в блоке администрирования
Hook::add('adminBlockAdmin', static function () {
    $url = route('admin.template.index');
    $label = __('template::template.template');
    $stats = statsTemplate();

    return '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>';
});
