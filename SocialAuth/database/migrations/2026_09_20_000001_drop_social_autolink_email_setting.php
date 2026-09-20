<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Настройка убрана: email от провайдера подтверждён, привязка к существующему
     * аккаунту делается всегда, а введённый руками адрес не привязывается никогда.
     * На работающих сайтах запись осталась бы мусором в settings.
     */
    public function up(): void
    {
        DB::table('settings')->where('name', 'social_autolink_email')->delete();
    }

    public function down(): void
    {
        if (! DB::table('settings')->where('name', 'social_autolink_email')->exists()) {
            DB::table('settings')->insert(['name' => 'social_autolink_email', 'value' => '']);
        }
    }
};
