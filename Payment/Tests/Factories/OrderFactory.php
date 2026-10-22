<?php

namespace Modules\Payment\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payment\Models\Order;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'email'    => $this->faker->safeEmail(),
            'type'     => Order::TYPE_BALANCE,
            'amount'   => 100,
            'currency' => 'RUB',
            'token'    => $this->faker->uuid(),
        ];
    }
}
