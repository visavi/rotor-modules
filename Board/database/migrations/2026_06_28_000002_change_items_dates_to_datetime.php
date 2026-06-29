<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getColumnType('items', 'created_at') === 'datetime') {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            $table->dateTime('created_at_dt')->nullable();
            $table->dateTime('updated_at_dt')->nullable();
            $table->dateTime('expires_at_dt')->nullable();
        });

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
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['expires_at']);
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

        Schema::table('items', function (Blueprint $table) {
            $table->integer('created_at_int')->nullable();
            $table->integer('updated_at_int')->nullable();
            $table->integer('expires_at_int')->nullable();
        });

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

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['expires_at']);
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
