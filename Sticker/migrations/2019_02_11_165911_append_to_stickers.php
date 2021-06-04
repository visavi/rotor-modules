<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use App\Models\Sticker;
use App\Models\StickersCategory;

final class AppendToStickers extends Migration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        $categories = array_map('basename', glob(base_path('modules/Sticker/resources/assets/*'), GLOB_ONLYDIR));

        foreach ($categories as $categoryName) {
            $category = StickersCategory::query()->create([
                'name'       => $categoryName,
                'created_at' => SITETIME,
            ]);

            $stickers = array_map('basename', glob(base_path('modules/Sticker/resources/assets/' . $categoryName . '/*.{gif,png,jpg,jpeg}'), GLOB_BRACE));

            foreach ($stickers as $stickerName) {
                Sticker::query()->create([
                    'category_id' => $category->id,
                    'name' => '/assets/modules/stickers/' . $categoryName . '/' . $stickerName,
                    'code' => ':' . getBodyName($stickerName),
                ]);
            }
        }

        clearCache('stickers');
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        $categories = array_map('basename', glob(base_path('modules/Sticker/resources/assets/*', GLOB_ONLYDIR)));

        foreach ($categories as $categoryName) {
            $category = StickersCategory::query()->where('name', $categoryName)->first();

            if ($category) {
                $category->delete();
                Sticker::query()->where('category_id', $category->id)->delete();
            }
        }

        clearCache('stickers');
    }
}
