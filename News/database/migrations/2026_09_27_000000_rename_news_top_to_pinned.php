<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Закрепление называется одинаково в новостях и в ленте (feeds.pinned):
     * FeedableTrait переносит флаг из записи в ленту по имени поля
     */
    public function up(): void
    {
        if (Schema::hasColumn('news', 'top')) {
            Schema::table('news', fn (Blueprint $table) => $table->renameColumn('top', 'pinned'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('news', 'pinned')) {
            Schema::table('news', fn (Blueprint $table) => $table->renameColumn('pinned', 'top'));
        }
    }
};
