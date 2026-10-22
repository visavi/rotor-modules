<?php

namespace Modules\Forum\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Forum\Models\Forum;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Forum>
 */
class ForumFactory extends Factory
{
    protected $model = Forum::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->words(2, true),
        ];
    }
}
