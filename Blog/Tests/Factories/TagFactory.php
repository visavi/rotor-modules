<?php

namespace Modules\Blog\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\Tag;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
        ];
    }
}
