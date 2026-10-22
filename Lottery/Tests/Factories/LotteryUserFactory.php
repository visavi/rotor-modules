<?php

namespace Modules\Lottery\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Lottery\Models\Lottery;
use Modules\Lottery\Models\LotteryUser;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<LotteryUser>
 */
class LotteryUserFactory extends Factory
{
    protected $model = LotteryUser::class;

    public function definition(): array
    {
        return [
            'lottery_id' => Lottery::factory(),
            'user_id'    => User::factory(),
            'number'     => 1,
            'created_at' => now(),
        ];
    }
}
