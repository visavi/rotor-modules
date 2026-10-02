<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Настройки лотереи, раньше жили в module.php
     */
    private const array SETTINGS = [
        ['name' => 'lottery_jackpot',      'value' => 1000000],
        ['name' => 'lottery_ticket_price', 'value' => 50],
        ['name' => 'lottery_min',          'value' => 1],
        ['name' => 'lottery_max',          'value' => 100],
    ];

    public function up(): void
    {
        DB::table('settings')->insertOrIgnore(self::SETTINGS);
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('name', array_column(self::SETTINGS, 'name'))
            ->delete();
    }
};
