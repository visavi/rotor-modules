<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Поздравление с днём рождения — только тем, кто заходил на сайт за столько лет
     */
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore(['name' => 'birthday_active_years', 'value' => 5]);
    }

    public function down(): void
    {
        DB::table('settings')->where('name', 'birthday_active_years')->delete();
    }
};
