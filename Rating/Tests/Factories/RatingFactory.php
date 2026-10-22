<?php

namespace Modules\Rating\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Rating\Models\Rating;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'recipient_id' => User::factory(),
            'text'         => $this->faker->sentence(),
            'vote'         => '+',
        ];
    }
}
