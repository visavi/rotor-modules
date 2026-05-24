<?php

use App\Classes\Hook;

// Ссылка в боковом меню
Hook::add('sidebarMenuEnd', static function () {
    $url = route('offers.index');
    $active = request()->is('offers*') ? ' active' : '';
    $label = __('index.offers');
    $stats = statsOffers();

    return '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon fa-regular fa-circle-question"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>';
}, 10);

// Ссылка в колонке footer
Hook::add('footerColumnMiddle', static function () {
    $url = route('offers.index');
    $label = __('index.offers');
    $stats = statsOffers();

    return '<li><a class="footer-item" href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span></li>';
});

// Ссылка в блоке редактора в админке
Hook::add('adminBlockAdmin', static function () {
    $url = route('admin.offers.index');
    $label = __('index.offers');
    $stats = statsOffers();

    return '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static function () {
    return '<a class="nav-link" href="' . route('offer.settings') . '">' . __('offer::offers.settings') . '</a>';
});
