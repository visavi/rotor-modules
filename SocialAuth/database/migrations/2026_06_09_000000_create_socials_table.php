<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('socials')) {
            Schema::create('socials', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->string('provider', 20);
                $table->string('provider_id', 100);
                $table->text('token')->nullable();
                $table->dateTime('created_at')->nullable();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['provider', 'provider_id']);
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('socials');
    }
};
