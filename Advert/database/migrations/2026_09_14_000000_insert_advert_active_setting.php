<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Раньше ноль ссылок означал и выключенный раздел, теперь для этого отдельная настройка
        $show = (int) DB::table('settings')->where('name', 'rekusershow')->value('value');

        DB::table('settings')->insertOrIgnore([
            ['name' => 'rekuseractive', 'value' => $show > 0 ? 1 : 0],
        ]);

        if ($show < 1) {
            DB::table('settings')->where('name', 'rekusershow')->update(['value' => 1]);
        }
    }

    public function down(): void
    {
        $active = (int) DB::table('settings')->where('name', 'rekuseractive')->value('value');

        if (! $active) {
            DB::table('settings')->where('name', 'rekusershow')->update(['value' => 0]);
        }

        DB::table('settings')->where('name', 'rekuseractive')->delete();
    }
};
