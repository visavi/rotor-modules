<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('paid_adverts')) {
            Schema::create('paid_adverts', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->nullable();
                $table->string('place', 20);
                $table->string('site', 100);
                $table->json('names');
                $table->string('color', 10)->nullable();
                $table->boolean('bold')->default(false);
                $table->string('comment')->nullable();
                $table->dateTime('created_at');
                $table->dateTime('deleted_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paid_adverts');
    }
};
