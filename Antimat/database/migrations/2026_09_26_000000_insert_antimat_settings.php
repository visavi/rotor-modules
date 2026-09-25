<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Antimat\Models\Antimat;

return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'antimat_replace', 'value' => '***'],
            ['name' => 'antimat_whole_word', 'value' => 0],
        ]);

        // Шаблоны кешируются навсегда: после переустановки модуля старый список не должен всплыть
        Antimat::flushCache();
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'antimat_replace',
            'antimat_whole_word',
        ])->delete();

        Antimat::flushCache();
    }
};
