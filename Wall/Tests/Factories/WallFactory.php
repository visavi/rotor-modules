<?php

namespace Modules\Wall\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Wall\Models\Wall;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Wall>
 */
class WallFactory extends Factory
{
    protected $model = Wall::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'author_id'  => User::factory(),
            'text'       => $this->faker->sentence(),
            'created_at' => now(),
        ];
    }
}
