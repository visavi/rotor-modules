<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Тема создаётся без счётчика — первый пост увеличивает его наблюдателем.
     * В строгом MySQL (DB_STRICT=true) вставка без значения падает, поэтому
     * счётчику нужен ноль по умолчанию. Смена одного умолчания не перестраивает таблицу
     */
    public function up(): void
    {
        if (! Schema::hasTable('topics')) {
            return;
        }

        Schema::table('topics', function (Blueprint $table) {
            $table->integer('count_posts')->default(0)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('topics')) {
            return;
        }

        Schema::table('topics', function (Blueprint $table) {
            $table->integer('count_posts')->change();
        });
    }
};
