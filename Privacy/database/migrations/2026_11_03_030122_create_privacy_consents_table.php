<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('privacy_consents')) {
            // Одна запись на пользователя: последнее данное согласие
            Schema::create('privacy_consents', function (Blueprint $table) {
                $table->integer('user_id')->primary();
                $table->integer('text_id');
                $table->string('ip', 45);
                $table->dateTime('created_at');

                // Число согласий у каждой версии в админке
                $table->index('text_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_consents');
    }
};
