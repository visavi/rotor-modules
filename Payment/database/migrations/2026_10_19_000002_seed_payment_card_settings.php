<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Цена 0 — место выключено: обновление не начнёт продавать карточки само
        DB::table('settings')->insertOrIgnore([
            ['name' => 'payment_price_card_sidebar', 'value' => 0],
            ['name' => 'payment_price_card_feed_start',  'value' => 0],
            ['name' => 'payment_price_card_feed_middle', 'value' => 0],
            ['name' => 'payment_price_card_feed_end',    'value' => 0],
            ['name' => 'payment_card_limit',          'value' => 3],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'payment_price_card_sidebar',
            'payment_price_card_feed_start',
            'payment_price_card_feed_middle',
            'payment_price_card_feed_end',
            'payment_card_limit',
        ])->delete();
    }
};
