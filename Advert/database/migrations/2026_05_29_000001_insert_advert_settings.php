<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'rekusershow',     'value' => 1],
            ['name' => 'rekuserprice',    'value' => 1000],
            ['name' => 'rekuserpoint',    'value' => 50],
            ['name' => 'rekuseroptprice', 'value' => 100],
            ['name' => 'rekusertime',     'value' => 12],
            ['name' => 'rekusertotal',    'value' => 10],
            ['name' => 'rekuserpost',     'value' => 10],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'rekusershow', 'rekuserprice', 'rekuserpoint',
            'rekuseroptprice', 'rekusertime', 'rekusertotal', 'rekuserpost',
        ])->delete();
    }
};
