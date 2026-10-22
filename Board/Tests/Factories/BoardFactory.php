<?php

namespace Modules\Board\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Board\Models\Board;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    protected $model = Board::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
        ];
    }
}
