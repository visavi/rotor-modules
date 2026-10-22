<?php

namespace Modules\Counter\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Counter\Models\Counter;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Counter>
 */
class CounterFactory extends Factory
{
    protected $model = Counter::class;

    public function definition(): array
    {
        return [
            'period'   => now()->startOfHour(),
            'allhosts' => 0,
            'allhits'  => 0,
            'dayhosts' => 0,
            'dayhits'  => 0,
            'hosts24'  => 0,
            'hits24'   => 0,
        ];
    }
}
