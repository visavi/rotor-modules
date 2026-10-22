<?php

namespace Modules\Counter\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Counter\Models\Counter24;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Counter24>
 */
class Counter24Factory extends Factory
{
    protected $model = Counter24::class;

    public function definition(): array
    {
        return [
            'period' => now()->startOfHour()->subHours($this->faker->unique()->numberBetween(0, 100000)),
            'hosts'  => 0,
            'hits'   => 0,
        ];
    }
}
