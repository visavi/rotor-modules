<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Настройки обратной связи
     */
    private const array SETTINGS = [
        ['name' => 'feedback_delivery', 'value' => 'messages'],
        ['name' => 'feedback_email',    'value' => ''],
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
