<?php

namespace Modules\HomeBuilder\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HomeBuilder\Models\Block;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    protected $model = Block::class;

    public function definition(): array
    {
        return [
            'type'       => Block::TEXT,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
