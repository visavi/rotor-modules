<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->nullable();
                $table->string('type', 20);
                $table->integer('amount');
                $table->string('currency');
                $table->string('token', 36);
                $table->string('payment_id', 36)->nullable();
                $table->string('payment_url')->nullable();
                $table->string('status', 20)->nullable();
                $table->json('data')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index('token');
                $table->index('payment_id');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
