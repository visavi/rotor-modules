<?php

use App\Classes\Hook;
use Modules\Payment\Models\Order;

// Добавляем ссылку на заказы в админку
Hook::add('adminBlockBoss', function ($content) {
    $ordersCount = Order::query()->count();

    return $content . '<i class="far fa-circle fa-lg text-muted"></i>
        <a href="/admin/orders">' . __('Payment::payments.orders') . '</a> <span class="badge bg-light text-dark">' . $ordersCount . '</span><br>' . PHP_EOL;
});
