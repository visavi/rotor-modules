<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Соцсети, включенные сразу после установки, остальные — по желанию в настройках
     */
    private array $settings = [
        'share_vk'        => 1,
        'share_ok'        => 1,
        'share_telegram'  => 1,
        'share_whatsapp'  => 1,
        'share_x'         => 1,
        'share_viber'     => 0,
        'share_facebook'  => 0,
        'share_reddit'    => 0,
        'share_pinterest' => 0,
        'share_linkedin'  => 0,
        'share_copy'      => 1,
    ];

    public function up(): void
    {
        $rows = [];
        foreach ($this->settings as $name => $value) {
            $rows[] = ['name' => $name, 'value' => $value];
        }

        DB::table('settings')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', array_keys($this->settings))->delete();
    }
};
