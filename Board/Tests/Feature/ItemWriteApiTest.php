<?php

namespace Modules\Board\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;
use Tests\ModuleTestCase;

class ItemWriteApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Board';

    private User $user;

    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Item::$morphName => Item::class]);

        $this->overrideSetting('boards_create', 1);
        $this->overrideSetting('boards_period', 30);
        $this->overrideSetting('board_title_min', 5);
        $this->overrideSetting('board_title_max', 50);
        $this->overrideSetting('board_text_min', 10);
        $this->overrideSetting('board_text_max', 1000);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
        $this->board = Board::query()->create(['parent_id' => 0, 'name' => 'Электроника', 'sort' => 1]);
    }

    public function testStoreRequiresToken(): void
    {
        $this->postJson('/api/items', $this->payload())->assertStatus(400);
    }

    public function testStoreCreatesItem(): void
    {
        $response = $this->postJson('/api/items', $this->payload(), $this->headers());

        $response->assertStatus(201)
            ->assertJsonPath('item.title', 'Продам телефон')
            ->assertJsonPath('item.price', 5000)
            ->assertJsonPath('item.phone', '+79001234567')
            ->assertJsonPath('item.category.id', $this->board->id)
            ->assertJsonPath('item.expired', false);

        // Счётчик категории растёт, как и на сайте
        $this->assertSame(1, $this->board->fresh()->count_items);
    }

    public function testStoreAttachesPendingMedia(): void
    {
        $file = File::query()->create([
            'relate_id'   => 0,
            'relate_type' => Item::$morphName,
            'path'        => '/uploads/boards/screen.jpg',
            'name'        => 'screen.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);

        $id = $this->postJson('/api/items', $this->payload(), $this->headers())->json('item.id');

        $this->assertSame($id, $file->fresh()->relate_id);
    }

    public function testStoreIsBlockedWhenSectionClosed(): void
    {
        $this->overrideSetting('boards_create', 0);

        $this->postJson('/api/items', $this->payload(), $this->headers())->assertStatus(422);
    }

    public function testStoreRejectsClosedCategory(): void
    {
        $this->board->update(['closed' => 1]);

        $this->postJson('/api/items', $this->payload(), $this->headers())->assertStatus(422);
    }

    public function testUpdateMovesCountersBetweenCategories(): void
    {
        $item = $this->createItem($this->user->id);
        $other = Board::query()->create(['parent_id' => 0, 'name' => 'Транспорт', 'sort' => 2]);

        $this->patchJson('/api/items/' . $item->id, [
            'category_id' => $other->id,
            'title'       => 'Продам велосипед',
            'text'        => 'Описание велосипеда для теста',
            'price'       => 3000,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('item.category.id', $other->id);

        $this->assertSame(1, $other->fresh()->count_items);
        $this->assertSame(0, $this->board->fresh()->count_items);
    }

    public function testForeignItemIsProtected(): void
    {
        $item = $this->createItem(User::factory()->create()->id);

        $this->patchJson('/api/items/' . $item->id, $this->payload(), $this->headers())->assertStatus(403);
        $this->deleteJson('/api/items/' . $item->id, [], $this->headers())->assertStatus(403);
    }

    public function testPublishTogglesExpiration(): void
    {
        $item = $this->createItem($this->user->id);

        $this->postJson('/api/items/' . $item->id . '/publish', [], $this->headers())
            ->assertOk()
            ->assertJsonPath('item.expired', true);

        $this->postJson('/api/items/' . $item->id . '/publish', [], $this->headers())
            ->assertOk()
            ->assertJsonPath('item.expired', false);
    }

    public function testDestroyRemovesItem(): void
    {
        $item = $this->createItem($this->user->id);

        $this->deleteJson('/api/items/' . $item->id, [], $this->headers())->assertOk();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    private function payload(): array
    {
        return [
            'category_id' => $this->board->id,
            'title'       => 'Продам телефон',
            'text'        => 'Описание телефона для теста',
            'price'       => 5000,
            'phone'       => '+7 (900) 123-45-67',
        ];
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }

    private function createItem(int $userId): Item
    {
        $item = Item::query()->create([
            'board_id'   => $this->board->id,
            'title'      => 'Продам телефон',
            'text'       => 'Описание телефона для теста',
            'user_id'    => $userId,
            'price'      => 5000,
            'phone'      => '+79001234567',
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->board->increment('count_items');

        return $item;
    }
}
