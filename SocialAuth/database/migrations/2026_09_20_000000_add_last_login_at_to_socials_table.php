<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Время последнего входа через провайдера.
     * created_at — момент привязки, поэтому для статистики входов нужна отдельная колонка
     */
    public function up(): void
    {
        if (! Schema::hasColumn('socials', 'last_login_at')) {
            Schema::table('socials', function (Blueprint $table) {
                $table->dateTime('last_login_at')->nullable()->after('created_at');

                $table->index('last_login_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('socials', 'last_login_at')) {
            Schema::table('socials', function (Blueprint $table) {
                $table->dropIndex(['last_login_at']);
                $table->dropColumn('last_login_at');
            });
        }
    }
};
