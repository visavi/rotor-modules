<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Номер тиража теперь тянется в момент розыгрыша, поэтому у текущего дня
     * его нет. Плюс один билет в руки закрепляется индексом, а не только
     * проверкой в контроллере
     */
    public function up(): void
    {
        Schema::table('lottery', function (Blueprint $table) {
            $table->smallInteger('number')->nullable()->change();
        });

        if (! $this->hasIndex('lottery_users', 'lottery_users_lottery_id_user_id_unique')) {
            $this->removeDuplicates();

            Schema::table('lottery_users', function (Blueprint $table) {
                $table->unique(['lottery_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('lottery_users', function (Blueprint $table) {
            $table->dropUnique(['lottery_id', 'user_id']);
        });
    }

    /**
     * Старые задвоенные билеты: оставляем первый, иначе индекс не создастся
     */
    private function removeDuplicates(): void
    {
        $keep = DB::table('lottery_users')
            ->selectRaw('min(id) as id')
            ->groupBy('lottery_id', 'user_id')
            ->pluck('id');

        DB::table('lottery_users')
            ->whereNotIn('id', $keep)
            ->delete();
    }

    private function hasIndex(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $existing) {
            if ($existing['name'] === $index) {
                return true;
            }
        }

        return false;
    }
};
