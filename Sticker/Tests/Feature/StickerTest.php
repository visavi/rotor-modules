<?php

namespace Modules\Sticker\Tests\Feature;

use App\Models\Sticker;
use App\Models\StickersCategory;
use Tests\ModuleTestCase;

/**
 * Модуль состоит из одной миграции: она заводит категории и раскладывает по ним картинки
 */
class StickerTest extends ModuleTestCase
{
    protected string $moduleName = 'Sticker';

    /** @var list<string> */
    private array $folders = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->folders = array_map('basename', glob(base_path('modules/Sticker/resources/assets/*'), GLOB_ONLYDIR) ?: []);

        // Сиды тестовой базы чистят таблицы стикеров, поэтому миграцию модуля прогоняем сами
        foreach (glob(base_path('modules/Sticker/database/migrations/*.php')) ?: [] as $file) {
            (require $file)->up();
        }
    }

    public function testCategoriesMatchAssetFolders(): void
    {
        $this->assertNotEmpty($this->folders, 'В модуле нет ни одной папки со стикерами');

        foreach ($this->folders as $folder) {
            $this->assertDatabaseHas('stickers_categories', ['name' => $folder]);
        }
    }

    public function testStickersAreLinkedToPublishedAssets(): void
    {
        $stickers = Sticker::query()
            ->whereIn('category_id', StickersCategory::query()->whereIn('name', $this->folders)->pluck('id'))
            ->get();

        $this->assertNotEmpty($stickers);

        foreach ($stickers as $sticker) {
            // Картинки попадают на сайт публикацией модуля, путь должен вести туда же
            $this->assertStringStartsWith('/assets/modules/stickers/', $sticker->name);
            $this->assertNotEmpty($sticker->code);
        }
    }

    public function testEveryImageGotItsSticker(): void
    {
        foreach ($this->folders as $folder) {
            $images = glob(base_path('modules/Sticker/resources/assets/' . $folder . '/*.{gif,png,jpg,jpeg}'), GLOB_BRACE) ?: [];

            $category = StickersCategory::query()->where('name', $folder)->first();

            $this->assertSame(
                count($images),
                Sticker::query()->where('category_id', $category->id)->count(),
                'Категория ' . $folder . ' получила не все картинки',
            );
        }
    }

    public function testStickersAreListedInApi(): void
    {
        $this->getJson('/api/stickers')
            ->assertOk()
            ->assertJsonFragment(['name' => $this->folders[0]]);
    }

    public function testRollbackRemovesStickers(): void
    {
        foreach (glob(base_path('modules/Sticker/database/migrations/*.php')) ?: [] as $file) {
            (require $file)->down();
        }

        foreach ($this->folders as $folder) {
            $this->assertDatabaseMissing('stickers_categories', ['name' => $folder]);
        }
    }
}
