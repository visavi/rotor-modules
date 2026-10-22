<?php

namespace Modules\Load\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Down>
 */
class DownFactory extends Factory
{
    protected $model = Down::class;

    public function definition(): array
    {
        return [
            'category_id' => Load::factory(),
            'title'       => $this->faker->unique()->sentence(3),
            'text'        => $this->faker->paragraph(),
            'user_id'     => User::factory(),
            'created_at'  => now(),
        ];
    }
}
