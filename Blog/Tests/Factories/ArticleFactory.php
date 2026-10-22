<?php

namespace Modules\Blog\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'category_id' => Blog::factory(),
            'user_id'     => User::factory(),
            'title'       => $this->faker->sentence(3),
            'slug'        => '',
            'text'        => $this->faker->paragraph(),
            'created_at'  => now(),
        ];
    }
}
