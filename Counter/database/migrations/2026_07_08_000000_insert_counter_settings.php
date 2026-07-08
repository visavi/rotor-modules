<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'incount', 'value' => 3],
        ]);

        // Стартовая строка счётчика: вся статистика хранится в единственной записи
        if (! DB::table('counters')->where('id', 1)->exists()) {
            DB::table('counters')->insert([
                'id'       => 1,
                'period'   => date('Y-m-d H:00:00'),
                'allhosts' => 0,
                'allhits'  => 0,
                'dayhosts' => 0,
                'dayhits'  => 0,
                'hosts24'  => 0,
                'hits24'   => 0,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('name', 'incount')->delete();
    }
};
