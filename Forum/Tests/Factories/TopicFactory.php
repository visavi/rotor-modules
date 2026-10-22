<?php

namespace Modules\Forum\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Topic;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        return [
            'forum_id'   => Forum::factory(),
            'title'      => $this->faker->sentence(3),
            'user_id'    => User::factory(),
            'created_at' => now(),
        ];
    }
}
