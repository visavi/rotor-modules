<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'postnews',          'value' => 10],
            ['name' => 'news_title_min',    'value' => 3],
            ['name' => 'news_title_max',    'value' => 50],
            ['name' => 'news_text_min',     'value' => 100],
            ['name' => 'news_text_max',     'value' => 100000],
            ['name' => 'feed_news_show',    'value' => 1],
            ['name' => 'feed_news_rating',  'value' => -10],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'postnews',
            'news_title_min',
            'news_title_max',
            'news_text_min',
            'news_text_max',
            'feed_news_show',
            'feed_news_rating',
        ])->delete();
    }
};
