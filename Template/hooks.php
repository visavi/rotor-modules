<?php

use App\Classes\Hook;

// Ссылка в боковом меню
Hook::add('sidebarMenuEnd', function (string $content) {
    $url = route('template.index');
    $active = request()->is('template*') ? ' active' : '';
    $label = __('template::template.template');
    $stats = statsTemplate();

    return $content . '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon far fa-file"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>' . PHP_EOL;
}, 10);

// Ссылка в блоке администрирования
Hook::add('adminBlockAdmin', function (string $content) {
    $url = route('admin.template.index');
    $label = __('template::template.template');
    $stats = statsTemplate();

    return $content . '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>' . PHP_EOL;
});
