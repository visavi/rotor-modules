<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Город объявления: по нему фильтруется список и строятся подсказки в форме
     */
    public function up(): void
    {
        if (! Schema::hasColumn('items', 'city')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('city', 50)->default('')->after('phone');
                $table->index('city');
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
