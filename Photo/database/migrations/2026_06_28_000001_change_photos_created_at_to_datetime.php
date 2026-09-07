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

    public function up(): void
    {
        if (Schema::getColumnType('photos', 'created_at') === 'datetime') {
            return;
        }

        Schema::table('photos', function (Blueprint $table) {
            $table->dateTime('created_at_dt')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // Конверсия в PHP/Carbon: полная историческая база таймзон (учитывает старый DST,
        // напр. Москва +04:00 до 2011) и точно совпадает с тем, как Eloquent прочитает datetime.
        // Не зависит от наличия tz-таблиц в MySQL.
        $tz = config('app.timezone');

        DB::table('photos')->select('id', 'created_at')->orderBy('id')->chunkById(2000, function ($rows) use ($tz) {
            foreach ($rows as $row) {
                DB::table('photos')->where('id', $row->id)->update([
                    'created_at_dt' => Date::createFromTimestamp($row->created_at, $tz)->format('Y-m-d H:i:s'),
                ]);
            }
        });

        $indexes = $this->indexNames('photos', [['created_at']]);
        Schema::table('photos', function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $index) {
                $table->dropIndex($index);
            }
            $table->dropColumn('created_at');
        });
        Schema::table('photos', fn (Blueprint $table) => $table->renameColumn('created_at_dt', 'created_at'));
        Schema::table('photos', fn (Blueprint $table) => $table->index('created_at'));
    }

    public function down(): void
    {
        if (Schema::getColumnType('photos', 'created_at') !== 'datetime') {
            return;
        }

        Schema::table('photos', fn (Blueprint $table) => $table->integer('created_at_int')->nullable());

        $tz = config('app.timezone');

        DB::table('photos')->select('id', 'created_at')->orderBy('id')->chunkById(2000, function ($rows) use ($tz) {
            foreach ($rows as $row) {
                DB::table('photos')->where('id', $row->id)->update([
                    'created_at_int' => Date::parse($row->created_at, $tz)->getTimestamp(),
                ]);
            }
        });

        $indexes = $this->indexNames('photos', [['created_at']]);
        Schema::table('photos', function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $index) {
                $table->dropIndex($index);
            }
            $table->dropColumn(['created_at', 'updated_at']);
        });
        Schema::table('photos', fn (Blueprint $table) => $table->renameColumn('created_at_int', 'created_at'));
        Schema::table('photos', fn (Blueprint $table) => $table->index('created_at'));
    }
};
