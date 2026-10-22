<?php

namespace Modules\Notebook\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notebook\Models\Notebook;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Notebook>
 */
class NotebookFactory extends Factory
{
    protected $model = Notebook::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'text'       => $this->faker->sentence(),
            'created_at' => now(),
        ];
    }
}
