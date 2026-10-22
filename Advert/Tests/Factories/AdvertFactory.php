<?php

namespace Modules\Advert\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Advert\Models\Advert;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Advert>
 */
class AdvertFactory extends Factory
{
    protected $model = Advert::class;

    public function definition(): array
    {
        return [
            'site'       => 'https://' . $this->faker->domainName(),
            'name'       => $this->faker->words(2, true),
            'user_id'    => User::factory(),
            'created_at' => now(),
        ];
    }
}
