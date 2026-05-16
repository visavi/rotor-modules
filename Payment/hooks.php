<?php

use App\Classes\Hook;
use Modules\Payment\Models\Order;

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', function (string $content) {
    return $content . '<a class="nav-link" href="/admin/payment-settings">' . __('payment::payments.settings') . '</a>' . PHP_EOL;
});

// Добавляем ссылку на заказы в админку
Hook::add('adminBlockBoss', function ($content) {
    $ordersCount = Order::query()->count();

    return $content . '<i class="far fa-circle text-muted"></i>
        <a href="/admin/orders">' . __('payment::payments.orders') . '</a> <span class="badge bg-adaptive">' . $ordersCount . '</span><br>' . PHP_EOL;
});
