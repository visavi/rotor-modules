<?php

namespace Modules\Gift\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<GiftsUser>
 */
class GiftsUserFactory extends Factory
{
    protected $model = GiftsUser::class;

    public function definition(): array
    {
        return [
            'gift_id'    => Gift::factory(),
            'user_id'    => User::factory(),
            'created_at' => now(),
            'deleted_at' => now()->addMonth(),
        ];
    }
}
