<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Настройки подарков, раньше жили в module.php
     */
    private const array SETTINGS = [
        ['name' => 'gift_per_page',  'value' => 24],
        ['name' => 'gift_days',      'value' => 365],
        ['name' => 'gift_max_users', 'value' => 10],
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
