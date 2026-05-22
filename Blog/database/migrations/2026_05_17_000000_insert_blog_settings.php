<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'article_moderation', 'value' => 0],
            ['name' => 'bloggroup',           'value' => 10],
            ['name' => 'blogpost',            'value' => 10],
            ['name' => 'blog_create',         'value' => 1],
            ['name' => 'blog_title_min',      'value' => 3],
            ['name' => 'blog_title_max',      'value' => 50],
            ['name' => 'blog_text_min',       'value' => 50],
            ['name' => 'blog_text_max',       'value' => 50000],
            ['name' => 'blog_tag_min',        'value' => 2],
            ['name' => 'blog_tag_max',        'value' => 30],
            ['name' => 'blog_category_min',   'value' => 3],
            ['name' => 'blog_category_max',   'value' => 50],
            ['name' => 'blog_point',          'value' => 5],
            ['name' => 'blog_money',          'value' => 500],
            ['name' => 'feed_articles_show',   'value' => 1],
            ['name' => 'feed_articles_rating', 'value' => -10],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'article_moderation', 'bloggroup', 'blogpost', 'blog_create',
            'blog_title_min', 'blog_title_max', 'blog_text_min', 'blog_text_max',
            'blog_tag_min', 'blog_tag_max', 'blog_category_min', 'blog_category_max',
            'blog_point', 'blog_money', 'feed_articles_show', 'feed_articles_rating',
        ])->delete();
    }
};
