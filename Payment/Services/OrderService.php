<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Modules\Payment\Models\Order;

class OrderService
{
    /**
     * Получает заказ по полю
     */
    public function getOrderByField(string $field, string $value): ?Order
    {
        return Order::query()
            ->where($field, $value)
            ->where('created_at', '>', Date::now()->subDay())
            ->first();
    }

    /**
     * Create order
     */
    public function createOrder(array $data): Order
    {
        return Order::query()->create([
            'user_id'     => getUser('id'),
            'type'        => $data['type'],
            'amount'      => $data['prices']['total'],
            'currency'    => setting('currency'),
            'token'       => Str::random(32),
            'email'       => $data['email'],
            'description' => $data['description'],
            'data'        => $data,
        ]);
    }
}
