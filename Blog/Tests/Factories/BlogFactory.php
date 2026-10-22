<?php

namespace Modules\Blog\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\Blog;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Blog>
 */
class BlogFactory extends Factory
{
    protected $model = Blog::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
        ];
    }
}
