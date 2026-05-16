<?php

use App\Classes\Hook;

// Ссылка в боковом меню
Hook::add('sidebarMenuEnd', function (string $content) {
    $url = route('offers.index');
    $active = request()->is('offers*') ? ' active' : '';
    $label = __('index.offers');
    $stats = statsOffers();

    return $content . '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon fa-regular fa-circle-question"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>' . PHP_EOL;
}, 10);

// Ссылка в колонке footer
Hook::add('footerColumnMiddle', function (string $content) {
    $url = route('offers.index');
    $label = __('index.offers');
    $stats = statsOffers();

    return $content . '<li><a class="footer-item" href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span></li>' . PHP_EOL;
});

// Ссылка в блоке редактора в админке
Hook::add('adminBlockAdmin', function (string $content) {
    $url = route('admin.offers.index');
    $label = __('index.offers');
    $stats = statsOffers();

    return $content . '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>' . PHP_EOL;
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', function (string $content) {
    $url = route('offer.settings');
    $label = __('offer::offers.settings');

    return $content . '<a class="nav-link" href="' . $url . '">' . $label . '</a>' . PHP_EOL;
});
