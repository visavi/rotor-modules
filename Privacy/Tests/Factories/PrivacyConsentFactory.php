<?php

namespace Modules\Privacy\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Privacy\Models\PrivacyConsent;
use Modules\Privacy\Models\PrivacyText;

/**
 * @extends Factory<PrivacyConsent>
 */
class PrivacyConsentFactory extends Factory
{
    protected $model = PrivacyConsent::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'text_id'    => PrivacyText::factory(),
            'ip'         => $this->faker->ipv4(),
            'created_at' => now(),
        ];
    }
}
