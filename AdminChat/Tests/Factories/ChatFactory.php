<?php

namespace Modules\AdminChat\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AdminChat\Models\Chat;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Chat>
 */
class ChatFactory extends Factory
{
    protected $model = Chat::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'text'       => $this->faker->sentence(),
            'ip'         => '127.0.0.1',
            'brow'       => 'test',
            'created_at' => now(),
        ];
    }
}
