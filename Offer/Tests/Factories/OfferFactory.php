<?php

namespace Modules\Offer\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Offer\Models\Offer;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'type'       => Offer::OFFER,
            'title'      => $this->faker->sentence(3),
            'text'       => $this->faker->paragraph(),
            'user_id'    => User::factory(),
            'status'     => Offer::WAIT,
            'created_at' => now(),
        ];
    }
}
