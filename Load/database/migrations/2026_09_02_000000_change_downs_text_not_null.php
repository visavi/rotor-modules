<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Описание файла обязательно на всех формах (down_text_min), а колонка
     * при выносе модуля из ядра оказалась nullable — возвращаем NOT NULL,
     * как у остальных модулей
     */
    public function up(): void
    {
        if (! Schema::hasTable('downs')) {
            return;
        }

        DB::table('downs')->whereNull('text')->update(['text' => '']);

        Schema::table('downs', function (Blueprint $table) {
            $table->text('text')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('downs')) {
            return;
        }

        Schema::table('downs', function (Blueprint $table) {
            $table->text('text')->nullable()->change();
        });
    }
};
