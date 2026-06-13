<?php

use App\Classes\Hook;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="/admin/payment-settings">' . __('payment::payments.settings') . '</a>');

// Ссылки в блок администратора
Hook::add('adminBlockBoss', static function () {
    return '<div class="col">
        <a href="/admin/paid-adverts" class="app-tile">
            <div class="app-tile-icon" style="background:#ffc107"><i class="fas fa-ad"></i></div>
            <div class="app-tile-label">' . __('payment::payments.paid_adverts.title') . '</div>
        </a>
    </div>
    <div class="col">
        <a href="/admin/orders" class="app-tile">
            <div class="app-tile-icon" style="background:#0d6efd"><i class="fas fa-shopping-cart"></i></div>
            <div class="app-tile-label">' . __('payment::payments.orders') . '<span class="badge bg-adaptive app-tile-badge">' . Order::query()->count() . '</span></div>
        </a>
    </div>';
});

// Платная реклама в шаблоне (верх/низ всех страниц), свободный верх продаёт себя сам
Hook::add('advertTop', static function () {
    $ad = PaidAdvert::renderAdvert(PaidAdvert::TOP_ALL)
        ?? '<i class="fas fa-ad"></i> <a class="small text-muted" href="/payments/advert" rel="nofollow">' . __('payment::payments.paid_adverts.your_advert_here') . '</a>';

    return '<div class="text-center">' . $ad . '</div>';
});
Hook::add('advertBottom', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::BOTTOM_ALL)) ? '<div class="text-center my-1">' . $ad . '</div>' : '');

// Платная реклама на главной странице
Hook::add('advertIndexTop', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::TOP)) ? '<div class="my-1">' . $ad . '</div>' : '');
Hook::add('advertIndexBottom', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::BOTTOM)) ? '<div class="my-1">' . $ad . '</div>' : '');

// Платная реклама на форуме
Hook::add('advertForum', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::FORUM)) ? '<div class="my-1">' . $ad . '</div>' : '');

// Баннер внизу сайдбара, ниже всех остальных хуков
Hook::add('sidebarFooterEnd', static fn () => '<li class="mt-3 text-center">
        <a class="btn btn-sm btn-adaptive w-100" href="/payments/advert" rel="nofollow"><i class="fas fa-ad"></i> ' . __('payment::payments.paid_adverts.site_advert') . '</a>
    </li>', -100);
