<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'payment_balance_enabled', 'value' => 0],
            ['name' => 'payment_balance_rate',    'value' => 100],
            ['name' => 'payment_balance_min',     'value' => 10],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'payment_balance_enabled',
            'payment_balance_rate',
            'payment_balance_min',
        ])->delete();
    }
};
