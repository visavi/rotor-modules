<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'photo_point', 'value' => 1],
            ['name' => 'photo_money', 'value' => 100],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', ['photo_point', 'photo_money'])->delete();
    }
};
