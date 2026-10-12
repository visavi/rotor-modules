<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('home_blocks')) {
            Schema::create('home_blocks', function (Blueprint $table) {
                $table->increments('id');
                $table->string('type', 50);
                $table->string('title', 100)->default('');
                $table->text('text')->nullable();
                $table->mediumText('code')->nullable();
                $table->string('template', 100)->default('');
                $table->text('sources')->nullable();
                $table->string('layout', 20)->default('');
                $table->unsignedTinyInteger('items')->default(5);
                $table->unsignedSmallInteger('cache_minutes')->default(0);
                $table->unsignedTinyInteger('width')->default(12);
                $table->string('visibility', 10)->default('all');
                $table->integer('sort')->default(0);
                $table->boolean('active')->default(true);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->dateTime('created_at');
                $table->dateTime('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('home_blocks');
    }
};
