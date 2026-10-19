<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('paid_adverts', 'site')) {
            Schema::table('paid_adverts', function (Blueprint $table) {
                $table->renameColumn('site', 'url');
            });
        }

        if (! Schema::hasColumn('paid_adverts', 'text')) {
            Schema::table('paid_adverts', function (Blueprint $table) {
                $table->string('text', 200)->nullable()->after('names');
            });
        }

        if (! Schema::hasColumn('paid_adverts', 'image')) {
            Schema::table('paid_adverts', function (Blueprint $table) {
                $table->string('image', 100)->nullable()->after('text');
            });
        }
    }

    public function down(): void
    {
        Schema::table('paid_adverts', function (Blueprint $table) {
            $table->dropColumn(['text', 'image']);
            $table->renameColumn('url', 'site');
        });
    }
};
