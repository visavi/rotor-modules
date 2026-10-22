<?php

namespace Modules\Forum\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Forum\Models\Vote;
use Modules\Forum\Models\VoteAnswer;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<VoteAnswer>
 */
class VoteAnswerFactory extends Factory
{
    protected $model = VoteAnswer::class;

    public function definition(): array
    {
        return [
            'vote_id' => Vote::factory(),
            'answer'  => $this->faker->word(),
        ];
    }
}
