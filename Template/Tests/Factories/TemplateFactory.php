<?php

namespace Modules\Template\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Template\Models\Template;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

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
