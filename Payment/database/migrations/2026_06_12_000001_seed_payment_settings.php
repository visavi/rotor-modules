<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'payment_yookassa_shop_id',    'value' => ''],
            ['name' => 'payment_yookassa_secret_key', 'value' => ''],
            ['name' => 'payment_price_top_all',       'value' => 80],
            ['name' => 'payment_price_top',           'value' => 35],
            ['name' => 'payment_price_forum',         'value' => 20],
            ['name' => 'payment_price_bottom_all',    'value' => 50],
            ['name' => 'payment_price_bottom',        'value' => 10],
            ['name' => 'payment_price_color',         'value' => 3],
            ['name' => 'payment_price_bold',          'value' => 3],
            ['name' => 'payment_price_name',          'value' => 1],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'payment_yookassa_shop_id',
            'payment_yookassa_secret_key',
            'payment_price_top_all',
            'payment_price_top',
            'payment_price_forum',
            'payment_price_bottom_all',
            'payment_price_bottom',
            'payment_price_color',
            'payment_price_bold',
            'payment_price_name',
        ])->delete();
    }
};
