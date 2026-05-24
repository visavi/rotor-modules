<?php

use App\Classes\Hook;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="/admin/payment-settings">' . __('payment::payments.settings') . '</a>');

// Ссылки в блок администратора
Hook::add('adminBlockBoss', static function () {
    $ordersCount = Order::query()->count();

    return '<i class="far fa-circle text-muted"></i> <a href="/admin/paid-adverts">' . __('payment::payments.paid_adverts.title') . '</a><br>'
        . '<i class="far fa-circle text-muted"></i> <a href="/admin/orders">' . __('payment::payments.orders') . '</a> <span class="badge bg-adaptive">' . $ordersCount . '</span><br>';
});

// Платная реклама в шаблоне (верх/низ всех страниц)
Hook::add('advertTop', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::TOP_ALL)) ? '<div class="text-center">' . $ad . '</div>' : '');
Hook::add('advertBottom', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::BOTTOM_ALL)) ? '<div class="text-center my-1">' . $ad . '</div>' : '');

// Платная реклама на главной странице
Hook::add('advertIndexTop', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::TOP)) ? '<div class="my-1">' . $ad . '</div>' : '');
Hook::add('advertIndexBottom', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::BOTTOM)) ? '<div class="my-1">' . $ad . '</div>' : '');

// Платная реклама на форуме
Hook::add('advertForum', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::FORUM)) ? '<div class="my-1">' . $ad . '</div>' : '');
