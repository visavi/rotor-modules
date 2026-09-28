<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Мессенджеры, в которых есть номер объявления: ключи через запятую
     */
    public function up(): void
    {
        if (! Schema::hasColumn('items', 'messengers')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('messengers', 100)->default('')->after('phone');
            });
        }
    }

    /**
     * Колонку не удаляем: на свежей установке её создаёт миграция таблицы,
     * и откат этой миграции не должен её отнимать
     */
    public function down(): void
    {
    }
};
