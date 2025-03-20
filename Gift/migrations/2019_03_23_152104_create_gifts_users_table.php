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
        if (! Schema::hasTable('gifts_users')) {
            Schema::create('gifts_users', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('gift_id');
                $table->integer('user_id');
                $table->integer('send_user_id')->nullable();
                $table->text('text')->nullable();
                $table->integer('created_at');
                $table->integer('deleted_at');
            });
        }
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        Schema::dropIfExists('gifts_users');
    }
};
