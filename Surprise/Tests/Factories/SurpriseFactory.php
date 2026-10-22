<?php

namespace Modules\Surprise\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Surprise\Models\Surprise;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Surprise>
 */
class SurpriseFactory extends Factory
{
    protected $model = Surprise::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'year'       => now()->year,
            'created_at' => now(),
        ];
    }
}
