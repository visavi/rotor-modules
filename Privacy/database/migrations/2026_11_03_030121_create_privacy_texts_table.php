<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('privacy_texts')) {
            // Версии текста согласия только добавляются: старые нужны как доказательство
            Schema::create('privacy_texts', function (Blueprint $table) {
                $table->increments('id');
                $table->mediumText('text');
                $table->dateTime('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_texts');
    }
};
