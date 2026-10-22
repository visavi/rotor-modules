<?php

namespace Modules\Forum\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Forum\Models\Vote;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    public function definition(): array
    {
        return [
            'title'      => $this->faker->sentence(3),
            'created_at' => now(),
        ];
    }
}
