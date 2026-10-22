<?php

namespace Modules\Load\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Load\Models\Load;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Load>
 */
class LoadFactory extends Factory
{
    protected $model = Load::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
        ];
    }
}
