<?php

namespace Modules\Transfer\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Transfer\Models\Transfer;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'recipient_id' => User::factory(),
            'text'         => $this->faker->sentence(),
            'created_at'   => now(),
        ];
    }
}
