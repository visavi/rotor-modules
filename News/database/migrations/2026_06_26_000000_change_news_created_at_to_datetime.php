<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dateTime('created_at_dt')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // Конверсия в PHP/Carbon: полная историческая база таймзон (учитывает старый DST,
        // напр. Москва +04:00 до 2011) и точно совпадает с тем, как Eloquent прочитает datetime.
        // Не зависит от наличия tz-таблиц в MySQL.
        $tz = config('app.timezone');

        DB::table('news')->select('id', 'created_at')->orderBy('id')->chunkById(2000, function ($rows) use ($tz) {
            foreach ($rows as $row) {
                DB::table('news')->where('id', $row->id)->update([
                    'created_at_dt' => Carbon::createFromTimestamp($row->created_at, $tz)->format('Y-m-d H:i:s'),
                ]);
            }
        });

        Schema::table('news', fn (Blueprint $table) => $table->dropColumn('created_at'));
        Schema::table('news', fn (Blueprint $table) => $table->renameColumn('created_at_dt', 'created_at'));
        Schema::table('news', fn (Blueprint $table) => $table->index('created_at'));
    }

    public function down(): void
    {
        Schema::table('news', fn (Blueprint $table) => $table->integer('created_at_int')->nullable());

        $tz = config('app.timezone');

        DB::table('news')->select('id', 'created_at')->orderBy('id')->chunkById(2000, function ($rows) use ($tz) {
            foreach ($rows as $row) {
                DB::table('news')->where('id', $row->id)->update([
                    'created_at_int' => Carbon::parse($row->created_at, $tz)->getTimestamp(),
                ]);
            }
        });

        Schema::table('news', fn (Blueprint $table) => $table->dropColumn(['created_at', 'updated_at']));
        Schema::table('news', fn (Blueprint $table) => $table->renameColumn('created_at_int', 'created_at'));
        Schema::table('news', fn (Blueprint $table) => $table->index('created_at'));
    }
};
