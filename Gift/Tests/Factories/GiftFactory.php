<?php

namespace Modules\Gift\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Gift\Models\Gift;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Gift>
 */
class GiftFactory extends Factory
{
    protected $model = Gift::class;

    public function definition(): array
    {
        return [
            'path'       => '/uploads/gifts/' . $this->faker->uuid() . '.png',
            'created_at' => now(),
        ];
    }
}
