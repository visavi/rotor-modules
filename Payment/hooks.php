<?php

use App\Classes\Hook;
use Modules\Payment\Models\Order;

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="/admin/payment-settings">' . __('payment::payments.settings') . '</a>');

// Добавляем ссылку на заказы в админку
Hook::add('adminBlockBoss', static function () {
    $ordersCount = Order::query()->count();

    return '<i class="far fa-circle text-muted"></i>
        <a href="/admin/orders">' . __('payment::payments.orders') . '</a> <span class="badge bg-adaptive">' . $ordersCount . '</span><br>';
});
