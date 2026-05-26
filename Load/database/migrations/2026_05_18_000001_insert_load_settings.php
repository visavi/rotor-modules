<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $settings = [
            ['name' => 'downlist',            'value' => 10],
            ['name' => 'downupload',          'value' => 1],
            ['name' => 'down_allow_links',    'value' => 0],
            ['name' => 'down_guest_download', 'value' => 1],
            ['name' => 'down_title_min',      'value' => 3],
            ['name' => 'down_title_max',      'value' => 50],
            ['name' => 'down_text_min',       'value' => 50],
            ['name' => 'down_text_max',       'value' => 10000],
            ['name' => 'down_link_min',       'value' => 5],
            ['name' => 'down_link_max',       'value' => 100],
            ['name' => 'down_category_min',   'value' => 3],
            ['name' => 'down_category_max',   'value' => 50],
            ['name' => 'down_point',          'value' => 5],
            ['name' => 'down_money',          'value' => 500],
            ['name' => 'feed_downs_show',     'value' => 1],
            ['name' => 'feed_downs_rating',   'value' => -10],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }

    public function down(): void
    {
    }
};
