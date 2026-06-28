<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * socials.created_at был TIMESTAMP (4 байта, диапазон 1970-2038, UTC-сдвиг сессии).
     * Приводим к DATETIME — единый тип дат во всём проекте, без проблемы 2038 и tz-конверсии.
     */
    public function up(): void
    {
        Schema::table('socials', function (Blueprint $table) {
            $table->dateTime('created_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('socials', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent()->change();
        });
    }
};
