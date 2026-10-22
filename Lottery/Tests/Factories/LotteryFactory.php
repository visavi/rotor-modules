<?php

namespace Modules\Lottery\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Lottery\Models\Lottery;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Lottery>
 */
class LotteryFactory extends Factory
{
    protected $model = Lottery::class;

    public function definition(): array
    {
        return [
            'day'    => now()->toDateString(),
            'amount' => 0,
        ];
    }
}
