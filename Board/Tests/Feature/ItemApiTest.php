<?php

namespace Modules\Board\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;
use Tests\ModuleTestCase;

class ItemApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Board';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Item::$morphName => Item::class]);

        $this->overrideSetting('currency', 'руб.');

        $this->user = User::factory()->create();
    }

    public function testViewIsAvailableForGuests(): void
    {
        $item = $this->createItem();

        $response = $this->getJson('/api/items/' . $item->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $item->id);
        $response->assertJsonPath('data.title', 'Test item');
        $response->assertJsonPath('data.url', $item->getViewUrl());
        $response->assertJsonPath('data.price', 500);
        $response->assertJsonPath('data.currency', 'руб.');
        $response->assertJsonPath('data.phone', '79001234567');
        $response->assertJsonPath('data.expired', false);
        $response->assertJsonPath('data.user.login', $this->user->login);
        $response->assertJsonPath('data.breadcrumbs.0.title', __('board::boards.boards'));
        $response->assertJsonPath('data.breadcrumbs.1.title', 'Test board');
        // Категория записи приходит объектом, как раздел у темы форума
        $response->assertJsonPath('data.category_id', $item->board_id);
        $response->assertJsonPath('data.category.name', 'Test board');
        $response->assertJsonPath('data.category.parent', null);
    }

    public function testViewSplitsMediaAndFiles(): void
    {
        $item = $this->createItem();

        $this->attachFile($item, 'photo.jpg', 'jpg', 'image/jpeg');
        $this->attachFile($item, 'manual.pdf', 'pdf', 'application/pdf');

        $response = $this->getJson('/api/items/' . $item->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data.media');
        $response->assertJsonCount(1, 'data.files');
        $response->assertJsonPath('data.media.0.name', 'photo.jpg');
        $response->assertJsonPath('data.files.0.name', 'manual.pdf');
    }

    public function testExpiredItemIsHiddenFromOtherUsers(): void
    {
        $item = $this->createItem(expired: true);

        // Гость истёкшее объявление видит, как и на сайте
        $this->getJson('/api/items/' . $item->id)
            ->assertOk()
            ->assertJsonPath('data.expired', true);

        $stranger = User::factory()->create(['apikey' => Str::random(32)]);

        $this->getJson('/api/items/' . $item->id, ['Authorization' => 'Bearer ' . $stranger->apikey])
            ->assertStatus(403);

        $author = User::query()->find($this->user->id);
        $author->update(['apikey' => Str::random(32)]);

        $this->getJson('/api/items/' . $item->id, ['Authorization' => 'Bearer ' . $author->apikey])
            ->assertOk();
    }

    public function testIndexHidesExpiredItems(): void
    {
        $this->createItem();
        $this->createItem(expired: true);

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.expired', false);
    }

    public function testCategoriesReturnTree(): void
    {
        $parent = Board::query()->create(['name' => 'Parent board']);
        Board::query()->create(['name' => 'Child board', 'parent_id' => $parent->id]);

        $this->getJson('/api/boards')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Parent board')
            ->assertJsonPath('data.0.children.0.name', 'Child board');
    }

    public function testViewNotFound(): void
    {
        $this->getJson('/api/items/100')->assertStatus(404);
    }

    private function createItem(bool $expired = false): Item
    {
        $board = Board::query()->create(['name' => 'Test board']);

        return Item::query()->create([
            'board_id'   => $board->id,
            'title'      => 'Test item',
            'text'       => 'Test item text',
            'user_id'    => $this->user->id,
            'price'      => 500,
            'phone'      => '79001234567',
            'active'     => true,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => $expired ? now()->subDay() : now()->addDay(),
        ]);
    }

    private function attachFile(Item $item, string $name, string $extension, string $mimeType): void
    {
        File::query()->create([
            'relate_id'   => $item->id,
            'relate_type' => Item::$morphName,
            'path'        => '/uploads/boards/' . $name,
            'name'        => $name,
            'size'        => 1024,
            'extension'   => $extension,
            'mime_type'   => $mimeType,
            'user_id'     => $this->user->id,
        ]);
    }
}
