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
        if (! Schema::hasTable('lottery_users')) {
            Schema::create('lottery_users', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('lottery_id');
                $table->integer('user_id');
                $table->smallInteger('number');
                $table->integer('created_at');
            });
        }
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        Schema::dropIfExists('lottery_users');
    }
};
