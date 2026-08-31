<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Срок админской рекламы был зашит в коде — неделя
        DB::table('settings')->insertOrIgnore([
            ['name' => 'rekadmintime', 'value' => 7],
        ]);

        // Ссылки выводятся случайной выборкой, ограничивать их число незачем
        DB::table('settings')->where('name', 'rekusertotal')->delete();
    }

    public function down(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'rekusertotal', 'value' => 10],
        ]);

        DB::table('settings')->where('name', 'rekadmintime')->delete();
    }
};
