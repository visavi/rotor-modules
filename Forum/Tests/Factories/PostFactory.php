<?php

namespace Modules\Forum\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'topic_id'   => Topic::factory(),
            'user_id'    => User::factory(),
            'text'       => $this->faker->paragraph(),
            'ip'         => '127.0.0.1',
            'brow'       => 'test',
            'created_at' => now(),
        ];
    }
}
