<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Реальные имена индексов по колонкам: базы со старого движка держат
     * индекс как <table>_<column>, а dropIndex(['column']) ищет
     * <table>_<column>_index и падает с 1091 на чужом имени.
     */
    private function indexNames(string $table, array $columns): array
    {
        $names = [];

        foreach (Schema::getIndexes($table) as $index) {
            if (! $index['primary'] && ! $index['unique'] && in_array($index['columns'], $columns, true)) {
                $names[] = $index['name'];
            }
        }

        return $names;
    }

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
        if (Schema::getColumnType('items', 'created_at') === 'datetime') {
            return;
        }

        $this->addTempColumns('items', 'dateTime', ['created_at_dt', 'updated_at_dt', 'expires_at_dt']);

        // Конверсия в PHP/Carbon: полная историческая база таймзон (учитывает старый DST,
        // напр. Москва +04:00 до 2011) и точно совпадает с тем, как Eloquent прочитает datetime.
        // Не зависит от наличия tz-таблиц в MySQL.
        $tz = config('app.timezone');

        DB::table('items')->select('id', 'created_at', 'updated_at', 'expires_at')->orderBy('id')->chunkById(2000, function ($rows) use ($tz) {
            foreach ($rows as $row) {
                DB::table('items')->where('id', $row->id)->update([
                    'created_at_dt' => Date::createFromTimestamp($row->created_at, $tz)->format('Y-m-d H:i:s'),
                    'updated_at_dt' => Date::createFromTimestamp($row->updated_at, $tz)->format('Y-m-d H:i:s'),
                    'expires_at_dt' => Date::createFromTimestamp($row->expires_at, $tz)->format('Y-m-d H:i:s'),
                ]);
            }
        });

        // Индексы дропаем явно (created_at, expires_at), чтобы пересоздание не словило дубликат имени.
        $indexes = $this->indexNames('items', [['created_at'], ['expires_at']]);
        Schema::table('items', function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $index) {
                $table->dropIndex($index);
            }
            $table->dropColumn(['created_at', 'updated_at', 'expires_at']);
        });
        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('created_at_dt', 'created_at');
            $table->renameColumn('updated_at_dt', 'updated_at');
            $table->renameColumn('expires_at_dt', 'expires_at');
        });
        Schema::table('items', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        if (Schema::getColumnType('items', 'created_at') !== 'datetime') {
            return;
        }

        $this->addTempColumns('items', 'integer', ['created_at_int', 'updated_at_int', 'expires_at_int']);

        $tz = config('app.timezone');

        DB::table('items')->select('id', 'created_at', 'updated_at', 'expires_at')->orderBy('id')->chunkById(2000, function ($rows) use ($tz) {
            foreach ($rows as $row) {
                DB::table('items')->where('id', $row->id)->update([
                    'created_at_int' => Date::parse($row->created_at, $tz)->getTimestamp(),
                    'updated_at_int' => Date::parse($row->updated_at, $tz)->getTimestamp(),
                    'expires_at_int' => Date::parse($row->expires_at, $tz)->getTimestamp(),
                ]);
            }
        });

        $indexes = $this->indexNames('items', [['created_at'], ['expires_at']]);
        Schema::table('items', function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $index) {
                $table->dropIndex($index);
            }
            $table->dropColumn(['created_at', 'updated_at', 'expires_at']);
        });
        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('created_at_int', 'created_at');
            $table->renameColumn('updated_at_int', 'updated_at');
            $table->renameColumn('expires_at_int', 'expires_at');
        });
        Schema::table('items', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('expires_at');
        });
    }
};
