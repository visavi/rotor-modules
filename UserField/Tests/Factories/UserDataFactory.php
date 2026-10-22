<?php

namespace Modules\UserField\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserField\Models\UserData;
use Modules\UserField\Models\UserField;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<UserData>
 */
class UserDataFactory extends Factory
{
    protected $model = UserData::class;

    public function definition(): array
    {
        return [
            'user_id'  => User::factory(),
            'field_id' => UserField::factory(),
        ];
    }
}
