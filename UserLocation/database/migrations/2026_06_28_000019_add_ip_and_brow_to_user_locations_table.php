<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Сохраняем IP и браузер последнего визита: online чистится, постоянного
     * хранилища нет. user_locations живёт по строке на юзера (updateOrCreate
     * по user_id) — храним тут. Видно только админу.
     */
    public function up(): void
    {
        Schema::table('user_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('user_locations', 'ip')) {
                $table->string('ip', 45)->nullable()->after('title');
            }

            if (! Schema::hasColumn('user_locations', 'brow')) {
                $table->string('brow', 25)->nullable()->after('ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_locations', function (Blueprint $table) {
            $columns = array_filter(
                ['ip', 'brow'],
                static fn ($column) => Schema::hasColumn('user_locations', $column),
            );

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
