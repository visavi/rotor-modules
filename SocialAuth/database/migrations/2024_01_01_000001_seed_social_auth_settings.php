<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private array $keys = [
        'social_google_client_id',
        'social_google_client_secret',
        'social_google_enabled',
        'social_github_client_id',
        'social_github_client_secret',
        'social_github_enabled',
        'social_yandex_client_id',
        'social_yandex_client_secret',
        'social_yandex_enabled',
        'social_vk_client_id',
        'social_vk_client_secret',
        'social_vk_enabled',
        'social_autolink_email',
    ];

    public function up(): void
    {
        $existing = DB::table('settings')->whereIn('name', $this->keys)->pluck('name')->all();

        $insert = [];
        foreach ($this->keys as $key) {
            if (! in_array($key, $existing, true)) {
                $insert[] = ['name' => $key, 'value' => ''];
            }
        }

        if ($insert) {
            DB::table('settings')->insert($insert);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', $this->keys)->delete();
    }
};
