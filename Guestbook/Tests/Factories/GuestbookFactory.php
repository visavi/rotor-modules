<?php

namespace Modules\Guestbook\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Guestbook\Models\Guestbook;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Guestbook>
 */
class GuestbookFactory extends Factory
{
    protected $model = Guestbook::class;

    public function definition(): array
    {
        return [
            'text'       => $this->faker->sentence(),
            'ip'         => '127.0.0.1',
            'brow'       => 'test',
            'created_at' => now(),
        ];
    }
}
