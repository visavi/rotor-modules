<?php

declare(strict_types=1);

use App\Migrations\Migration;
use Modules\Lottery\Models\Lottery;

final class SeedToLottery extends Migration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        $config = Lottery::getConfig();

        Lottery::query()->create([
            'day'    => date('Y-m-d'),
            'amount' => $config['jackpot'],
            'number' => mt_rand($config['numberRange'][0], $config['numberRange'][1]),
        ]);
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        Lottery::query()->truncate();
    }
}
