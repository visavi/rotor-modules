<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('templates')) {
            Schema::create('templates', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->string('title', 100);
                $table->text('text');
                $table->integer('created_at');

                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
