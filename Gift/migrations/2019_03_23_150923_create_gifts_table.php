<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateGiftsTable extends Migration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        if (! Schema::hasTable('gifts')) {
            Schema::create('gifts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50)->nullable();
                $table->string('path', 100);
                $table->integer('price')->default(0);
                $table->integer('created_at');
            });
        }
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        Schema::dropIfExists('gifts');
    }
}
