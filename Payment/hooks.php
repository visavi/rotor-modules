<?php

use App\Classes\Hook;
use Modules\Payment\Models\Order;

// Добавляем ссылку на заказы в админку
Hook::add('adminBlockBoss', function ($content) {
    $ordersCount = Order::query()->count();

    return $content . '<i class="far fa-circle text-muted"></i>
        <a href="/admin/orders">' . __('payment::payments.orders') . '</a> <span class="badge bg-adaptive">' . $ordersCount . '</span><br>' . PHP_EOL;
});
