<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('notebooks')) {
            Schema::create('notebooks', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->text('text');
                $table->dateTime('created_at');
                // На обновляемых сайтах колонку добавляет миграция перевода дат,
                // свежей установке она нужна сразу — иначе заметку не сохранить
                $table->dateTime('updated_at')->nullable();

                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notebooks');
    }
};
