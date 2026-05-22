<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'fotolist',        'value' => 5],
            ['name' => 'photogroup',      'value' => 10],
            ['name' => 'photos_create',   'value' => 1],
            ['name' => 'photo_title_min', 'value' => 3],
            ['name' => 'photo_title_max', 'value' => 50],
            ['name' => 'photo_text_min',  'value' => 0],
            ['name' => 'photo_text_max',  'value' => 1000],
            ['name' => 'feed_photos_show',   'value' => 1],
            ['name' => 'feed_photos_rating', 'value' => -10],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'fotolist', 'photogroup', 'photos_create',
            'photo_title_min', 'photo_title_max',
            'photo_text_min', 'photo_text_max',
            'feed_photos_show', 'feed_photos_rating',
        ])->delete();
    }
};
