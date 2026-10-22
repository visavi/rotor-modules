<?php

namespace Modules\Forum\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Forum\Models\Bookmark;
use Modules\Forum\Models\Topic;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
{
    protected $model = Bookmark::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'topic_id'    => Topic::factory(),
            'count_posts' => 0,
        ];
    }
}
