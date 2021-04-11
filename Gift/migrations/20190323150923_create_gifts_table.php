<?php

declare(strict_types=1);

use App\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

final class CreateGiftsTable extends Migration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        if (! $this->schema->hasTable('gifts')) {
            $this->schema->create('gifts', function (Blueprint $table) {
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
        $this->schema->dropIfExists('gifts');
    }
}
