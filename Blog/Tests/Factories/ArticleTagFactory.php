<?php

namespace Modules\Blog\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\ArticleTag;
use Modules\Blog\Models\Tag;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<ArticleTag>
 */
class ArticleTagFactory extends Factory
{
    protected $model = ArticleTag::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'tag_id'     => Tag::factory(),
        ];
    }
}
