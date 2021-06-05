<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
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
        if (Schema::hasTable('lottery')) {
            Lottery::query()->truncate();
        }
    }
}
