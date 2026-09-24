<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Счётчик просмотров объявления. Колонка есть только в миграции создания таблицы,
     * базы со старого движка её не получили и падают на просмотре с 1054 Unknown column
     */
    public function up(): void
    {
        if (! Schema::hasColumn('items', 'visits')) {
            Schema::table('items', function (Blueprint $table) {
                $table->integer('visits')->default(0)->after('active');
            });
        }
    }

    /**
     * Колонку не удаляем: на свежей установке её создаёт миграция таблицы,
     * и откат этой миграции не должен её отнимать
     */
    public function down(): void {}
};
