<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'sendmoneypoint', 'value' => 50],
            ['name' => 'listtransfers',  'value' => 10],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'sendmoneypoint', 'listtransfers',
        ])->delete();
    }
};
