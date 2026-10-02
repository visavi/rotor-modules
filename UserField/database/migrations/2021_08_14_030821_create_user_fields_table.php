<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('user_fields')) {
            Schema::create('user_fields', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sort');
                // enum требовал ALTER на каждый новый тип, строка — нет
                $table->string('type', 20);
                $table->string('name', 50);
                $table->string('placeholder', 100)->default('');
                $table->string('hint')->default('');
                // Варианты списка, по одному на строку
                $table->text('options')->nullable();
                $table->integer('min');
                $table->integer('max');
                $table->boolean('required')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_fields');
    }
};
