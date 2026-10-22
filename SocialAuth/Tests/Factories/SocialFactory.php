<?php

namespace Modules\SocialAuth\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SocialAuth\Models\Social;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Social>
 */
class SocialFactory extends Factory
{
    protected $model = Social::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'provider'    => 'github',
            'provider_id' => $this->faker->unique()->numerify('##########'),
        ];
    }
}
