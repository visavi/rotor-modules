<?php

namespace Modules\Counter\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Counter\Models\Counter31;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Counter31>
 */
class Counter31Factory extends Factory
{
    protected $model = Counter31::class;

    public function definition(): array
    {
        return [
            'period' => now()->startOfDay()->subDays($this->faker->unique()->numberBetween(0, 100000)),
            'hosts'  => 0,
            'hits'   => 0,
        ];
    }
}
