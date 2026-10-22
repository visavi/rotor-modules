<?php

namespace Modules\News\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\News\Models\News;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        return [
            'title'      => $this->faker->sentence(3),
            'text'       => $this->faker->paragraph(),
            'user_id'    => User::factory(),
            'created_at' => now(),
        ];
    }
}
