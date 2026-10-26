<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'offer_point', 'value' => 5],
            ['name' => 'offer_money', 'value' => 500],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', ['offer_point', 'offer_money'])->delete();
    }
};
