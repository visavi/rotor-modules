<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_fields', function (Blueprint $table) {
            // enum требовал ALTER на каждый новый тип, строка — нет
            $table->string('type', 20)->change();
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
