<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Создаёт только отсутствующие временные колонки: упавшая миграция могла
     * оставить их с прошлого запуска, повторный запуск не должен падать.
     */
    private function addTempColumns(string $table, string $type, array $cols): void
    {
        $missing = array_filter($cols, static fn ($col) => ! Schema::hasColumn($table, $col));

        if ($missing) {
            Schema::table($table, static function (Blueprint $blueprint) use ($type, $missing) {
                foreach ($missing as $col) {
                    $blueprint->{$type}($col)->nullable();
                }
            });
        }
    }

    public function up(): void
    {
        if (Schema::getColumnType('articles', 'created_at') === 'datetime') {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            // без after() — новые колонки добавляются в конец таблицы (как в стандартном Laravel)
            $table->dateTime('created_at_dt')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // Конверсия в PHP/Carbon: полная историческая база таймзон (учитывает старый DST,
        // напр. Москва +04:00 до 2011) и точно совпадает с тем, как Eloquent прочитает datetime.
        // Не зависит от наличия tz-таблиц в MySQL.
        $tz = config('app.timezone');

        DB::table('articles')->select('id', 'created_at')->orderBy('id')->chunkById(2000, function ($rows) use ($tz) {
            foreach ($rows as $row) {
                DB::table('articles')->where('id', $row->id)->update([
                    'created_at_dt' => Date::createFromTimestamp($row->created_at, $tz)->format('Y-m-d H:i:s'),
                ]);
            }
        });

        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn('created_at'));
        Schema::table('articles', fn (Blueprint $table) => $table->renameColumn('created_at_dt', 'created_at'));
        Schema::table('articles', fn (Blueprint $table) => $table->index('created_at'));
    }

    public function down(): void
    {
        if (Schema::getColumnType('articles', 'created_at') !== 'datetime') {
            return;
        }

        $this->addTempColumns('articles', 'integer', ['created_at_int']);

        $tz = config('app.timezone');

        DB::table('articles')->select('id', 'created_at')->orderBy('id')->chunkById(2000, function ($rows) use ($tz) {
            foreach ($rows as $row) {
                DB::table('articles')->where('id', $row->id)->update([
                    'created_at_int' => Date::parse($row->created_at, $tz)->getTimestamp(),
                ]);
            }
        });

        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn(['created_at', 'updated_at']));
        Schema::table('articles', fn (Blueprint $table) => $table->renameColumn('created_at_int', 'created_at'));
        Schema::table('articles', fn (Blueprint $table) => $table->index('created_at'));
    }
};
