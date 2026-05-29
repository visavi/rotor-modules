<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Регистрируем неймспейс переводов модуля на случай, если провайдер ещё не загрузил его
        app('translator')->addNamespace('transfer', __DIR__ . '/../../resources/lang');

        DB::table('notices')->insertOrIgnore([
            'type'       => 'transfer',
            'name'       => __('transfer::transfers.notice_name'),
            'text'       => __('transfer::transfers.notice_text'),
            'user_id'    => 1,
            'protect'    => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public function down(): void
    {
        DB::table('notices')->where('type', 'transfer')->delete();
    }
};
