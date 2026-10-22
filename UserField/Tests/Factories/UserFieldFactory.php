<?php

namespace Modules\UserField\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserField\Models\UserField;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<UserField>
 */
class UserFieldFactory extends Factory
{
    protected $model = UserField::class;

    public function definition(): array
    {
        return [
            'sort' => 0,
            'type' => UserField::INPUT,
            'name' => $this->faker->words(2, true),
            'min'  => 0,
            'max'  => 100,
        ];
    }
}
