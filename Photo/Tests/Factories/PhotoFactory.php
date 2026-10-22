<?php

namespace Modules\Photo\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Photo\Models\Photo;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'title'      => $this->faker->sentence(3),
            'text'       => $this->faker->sentence(),
            'created_at' => now(),
        ];
    }
}
