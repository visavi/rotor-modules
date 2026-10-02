<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // enum требовал ALTER на каждый новый тип, строка — нет.
        // На свежей установке колонка уже строка — change() ничего не меняет
        Schema::table('user_fields', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });

        // Свежая установка получает колонки из create_user_fields_table
        if (Schema::hasColumn('user_fields', 'placeholder')) {
            return;
        }

        Schema::table('user_fields', function (Blueprint $table) {
            $table->string('placeholder', 100)->default('')->after('name');
            $table->string('hint')->default('')->after('placeholder');
            // Варианты списка, по одному на строку
            $table->text('options')->nullable()->after('hint');
        });
    }

    public function down(): void
    {
        Schema::table('user_fields', function (Blueprint $table) {
            $table->dropColumn(['placeholder', 'hint', 'options']);
            $table->enum('type', ['input', 'textarea'])->change();
        });
    }
};
