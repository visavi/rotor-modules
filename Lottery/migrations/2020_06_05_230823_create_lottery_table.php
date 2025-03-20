<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        if (! Schema::hasTable('lottery')) {
            Schema::create('lottery', function (Blueprint $table) {
                $table->increments('id');
                $table->date('day');
                $table->integer('amount');
                $table->smallInteger('number');

                $table->index('day');
            });
        }
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        Schema::dropIfExists('lottery');
    }
};
