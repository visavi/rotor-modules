<?php

declare(strict_types=1);

use App\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

final class CreateLotteryUsersTable extends Migration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        if (! $this->schema->hasTable('lottery_users')) {
            $this->schema->create('lottery_users', function (Blueprint $table) {
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
        $this->schema->dropIfExists('lottery_users');
    }
}
