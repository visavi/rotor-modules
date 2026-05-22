<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'postoffers',       'value' => 10],
            ['name' => 'addofferspoint',   'value' => 50],
            ['name' => 'offer_title_min',  'value' => 3],
            ['name' => 'offer_title_max',  'value' => 50],
            ['name' => 'offer_text_min',   'value' => 5],
            ['name' => 'offer_text_max',   'value' => 1000],
            ['name' => 'offer_reply_min',  'value' => 5],
            ['name' => 'offer_reply_max',  'value' => 3000],
            ['name' => 'feed_offers_show',   'value' => 1],
            ['name' => 'feed_offers_rating', 'value' => -5],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'postoffers',
            'addofferspoint',
            'offer_title_min',
            'offer_title_max',
            'offer_text_min',
            'offer_text_max',
            'offer_reply_min',
            'offer_reply_max',
            'feed_offers_show',
            'feed_offers_rating',
        ])->delete();
    }
};
