<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * created_at был TIMESTAMP useCurrent (4 байта, 1970-2038, UTC-сдвиг сессии).
     * Приводим к DATETIME — единый тип дат во всём проекте. created_at = время
     * последнего визита, проставляется вручную в middleware (now()).
     */
    public function up(): void
    {
        Schema::table('user_locations', function (Blueprint $table) {
            $table->dateTime('created_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_locations', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent()->change();
        });
    }
};
