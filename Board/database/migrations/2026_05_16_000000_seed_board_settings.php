<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'boards_create',       'value' => 1],
            ['name' => 'boards_period',        'value' => 30],
            ['name' => 'boards_per_page',      'value' => 10],
            ['name' => 'board_title_min',      'value' => 3],
            ['name' => 'board_title_max',      'value' => 50],
            ['name' => 'board_text_min',       'value' => 10],
            ['name' => 'board_text_max',       'value' => 5000],
            ['name' => 'board_category_min',   'value' => 3],
            ['name' => 'board_category_max',   'value' => 50],
            ['name' => 'feed_items_show',      'value' => 1],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'boards_create',
            'boards_period',
            'boards_per_page',
            'board_title_min',
            'board_title_max',
            'board_text_min',
            'board_text_max',
            'board_category_min',
            'board_category_max',
            'feed_items_show',
        ])->delete();
    }
};
