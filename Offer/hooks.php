<?php

use App\Classes\Hook;

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    $url = route('offers.index');
    $active = request()->is('offers*') ? ' active' : '';
    $label = __('offer::offers.offers');
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
    $label = __('offer::offers.offers');
    $stats = statsOffers();

    return '<li><a class="footer-item" href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span></li>';
});

// Ссылка в блоке редактора в админке
Hook::add('adminBlockAdmin', static function () {
    return '<div class="col">
        <a href="' . route('admin.offers.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#d63384"><i class="fa-regular fa-circle-question"></i></div>
            <div class="app-tile-label">' . __('offer::offers.offers') . '<span class="badge bg-adaptive app-tile-badge">' . statsOffers() . '</span></div>
        </a>
    </div>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static function () {
    return '<a class="nav-link" href="' . route('offer.settings') . '">' . __('offer::offers.settings') . '</a>';
});
