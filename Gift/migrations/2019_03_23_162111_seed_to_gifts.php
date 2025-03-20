<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Gift\Models\Gift;

return new class extends Migration {
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        $gifts = array_map('basename', glob(base_path('modules/Gift/resources/assets/*.{gif,png,jpg,jpeg}'), GLOB_BRACE));

        $data = [];
        foreach ($gifts as $gift) {
            $data[] = [
                'name' => '',
                'path'       => '/assets/modules/gifts/' . $gift,
                'price'      => mt_rand(1, 10) * 100,
                'created_at' => SITETIME,
            ];
        }

        Gift::query()->insert($data);
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        if (Schema::hasTable('gifts')) {
            Gift::query()->truncate();
        }
    }
};
