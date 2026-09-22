<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if ($this->hasIndex()) {
            return;
        }

        Schema::table('downs', function (Blueprint $table) {
            // Анкета показывает число файлов пользователя,
            // без индекса такой подсчёт сканирует всю таблицу
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        if (! $this->hasIndex()) {
            return;
        }

        Schema::table('downs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }

    private function hasIndex(): bool
    {
        return Schema::hasIndex('downs', ['user_id']);
    }
};
