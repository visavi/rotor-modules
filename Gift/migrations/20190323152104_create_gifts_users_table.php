<?php

declare(strict_types=1);

use App\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

final class CreateGiftsUsersTable extends Migration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        if (! $this->schema->hasTable('gifts_users')) {
            $this->schema->create('gifts_users', function (Blueprint $table) {
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
        $this->schema->dropIfExists('gifts_users');
    }
}
