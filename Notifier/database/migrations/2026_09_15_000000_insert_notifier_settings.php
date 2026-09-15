<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Настройки уведомлений о новых личных сообщениях
     */
    private const array SETTINGS = [
        ['name' => 'notifier_active',   'value' => 1],
        ['name' => 'notifier_interval', 'value' => 60],
        ['name' => 'notifier_sound',    'value' => 'ding.mp3'],
        ['name' => 'notifier_volume',   'value' => 70],
        ['name' => 'notifier_title',    'value' => 1],
        ['name' => 'notifier_desktop',  'value' => 0],
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
