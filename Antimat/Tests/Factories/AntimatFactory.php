<?php

namespace Modules\Antimat\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Antimat\Models\Antimat;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Antimat>
 */
class AntimatFactory extends Factory
{
    protected $model = Antimat::class;

    public function definition(): array
    {
        return [
            'string' => $this->faker->unique()->word(),
        ];
    }
}
