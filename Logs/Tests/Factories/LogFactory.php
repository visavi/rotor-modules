<?php

namespace Modules\Logs\Tests\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Logs\Models\Log;

/**
 * Запись с обязательными полями: остальное — значения по умолчанию из миграции
 *
 * @extends Factory<Log>
 */
class LogFactory extends Factory
{
    protected $model = Log::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ip'      => '127.0.0.1',
            'brow'    => 'test',
        ];
    }
}
