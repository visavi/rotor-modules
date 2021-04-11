<?php

declare(strict_types=1);

use App\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

final class CreateLotteryTable extends Migration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        if (! $this->schema->hasTable('lottery')) {
            $this->schema->create('lottery', function (Blueprint $table) {
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
        $this->schema->dropIfExists('lottery');
    }
}
