<?php

namespace Modules\UserLocation\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserLocation\Models\UserLocation;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<UserLocation>
 */
class UserLocationFactory extends Factory
{
    protected $model = UserLocation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'path'    => '/',
            'title'   => $this->faker->sentence(3),
        ];
    }
}
