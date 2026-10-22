<?php

namespace Modules\Payment\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payment\Models\PaidAdvert;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<PaidAdvert>
 */
class PaidAdvertFactory extends Factory
{
    protected $model = PaidAdvert::class;

    public function definition(): array
    {
        return [
            'place'      => PaidAdvert::TOP,
            'url'        => 'https://' . $this->faker->domainName(),
            'names'      => [],
            'created_at' => now(),
        ];
    }
}
