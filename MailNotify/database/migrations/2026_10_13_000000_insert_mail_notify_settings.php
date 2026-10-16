<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Настройки почтовой рассылки. До выноса в модуль они были в ядре: на старых
     * сайтах строки уже есть, insertOrIgnore сохранит их значения
     */
    private const array SETTINGS = [
        ['name' => 'sendprivatmailday', 'value' => 3],
        ['name' => 'sendmailpacket',    'value' => 3],
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
