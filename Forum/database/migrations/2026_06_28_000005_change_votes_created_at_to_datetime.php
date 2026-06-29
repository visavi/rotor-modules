<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Чанковая конверсия с транзакцией на чанк (см. миграцию posts).
     */
    private function convert(string $table, array $cols, callable $map): void
    {
        DB::table($table)->select(array_merge(['id'], $cols))->orderBy('id')
            ->chunkById(5000, function ($rows) use ($table, $map) {
                DB::transaction(function () use ($rows, $table, $map) {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update($map($row));
                    }
                });
            });
    }

    public function up(): void
    {
        if (Schema::getColumnType('votes', 'created_at') === 'datetime') {
            return;
        }

        $toDt = static fn ($v) => $v ? Date::createFromTimestamp($v, config('app.timezone'))->format('Y-m-d H:i:s') : null;

        Schema::table('votes', fn (Blueprint $table) => $table->dateTime('created_at_dt')->nullable());
        $this->convert('votes', ['created_at'], static fn ($r) => [
            'created_at_dt' => $toDt($r->created_at),
        ]);
        Schema::table('votes', fn (Blueprint $table) => $table->dropColumn('created_at'));
        Schema::table('votes', fn (Blueprint $table) => $table->renameColumn('created_at_dt', 'created_at'));
    }

    public function down(): void
    {
        if (Schema::getColumnType('votes', 'created_at') !== 'datetime') {
            return;
        }

        $toInt = static fn ($v) => $v ? Date::parse($v, config('app.timezone'))->getTimestamp() : null;

        Schema::table('votes', fn (Blueprint $table) => $table->integer('created_at_int')->nullable());
        $this->convert('votes', ['created_at'], static fn ($r) => [
            'created_at_int' => $toInt($r->created_at),
        ]);
        Schema::table('votes', fn (Blueprint $table) => $table->dropColumn('created_at'));
        Schema::table('votes', fn (Blueprint $table) => $table->renameColumn('created_at_int', 'created_at'));
    }
};
