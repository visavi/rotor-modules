<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'bookadds',           'value' => 1],
            ['name' => 'bookpost',           'value' => 10],
            ['name' => 'guestbook_text_min', 'value' => 5],
            ['name' => 'guestbook_text_max', 'value' => 1000],
            ['name' => 'guest_moderation',   'value' => 0],
            ['name' => 'guestbook_point',    'value' => 1],
            ['name' => 'guestbook_money',    'value' => 50],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'bookadds',
            'bookpost',
            'guestbook_text_min',
            'guestbook_text_max',
            'guest_moderation',
            'guestbook_point',
            'guestbook_money',
        ])->delete();
    }
};
