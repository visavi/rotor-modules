<?php

namespace Modules\Board\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'board_id'   => Board::factory(),
            'title'      => $this->faker->sentence(3),
            'text'       => $this->faker->paragraph(),
            'user_id'    => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addMonth(),
        ];
    }
}
