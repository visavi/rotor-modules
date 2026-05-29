<?php

use App\Classes\Hook;

// Ссылка в колонке footer
Hook::add('footerColumnMiddle', static fn () => '<li>
    <a class="footer-item" href="' . route('offers.index') . '">
        ' . __('offer::offers.offers') . '
    </a>
     <span class="badge bg-adaptive">' . statsOffers() . '</span>
 </li>');

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
