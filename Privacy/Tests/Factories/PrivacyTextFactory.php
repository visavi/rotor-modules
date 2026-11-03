<?php

namespace Modules\Privacy\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Privacy\Models\PrivacyText;

/**
 * @extends Factory<PrivacyText>
 */
class PrivacyTextFactory extends Factory
{
    protected $model = PrivacyText::class;

    public function definition(): array
    {
        return [
            'text'       => '<p>' . $this->faker->paragraph() . '</p>',
            'created_at' => now(),
        ];
    }
}
