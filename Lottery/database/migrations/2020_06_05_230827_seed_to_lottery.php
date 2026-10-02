<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Lottery\Models\Lottery;

return new class extends Migration {
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        // Настроек ещё нет — их создаёт миграция позже, банк стартовый по умолчанию.
        // Номер пустой: он тянется в момент розыгрыша, как и у любого текущего тиража
        Lottery::query()->create([
            'day'    => date('Y-m-d'),
            'amount' => 1000000,
            'number' => null,
        ]);
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        if (Schema::hasTable('lottery')) {
            Lottery::query()->truncate();
        }
    }
};
