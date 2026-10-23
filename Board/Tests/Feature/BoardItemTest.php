<?php

namespace Modules\Board\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;
use Tests\ModuleTestCase;

class BoardItemTest extends ModuleTestCase
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
        $this->overrideSetting('boards_per_page', 10);
        $this->overrideSetting('board_title_min', 5);
        $this->overrideSetting('board_title_max', 50);
        $this->overrideSetting('board_text_min', 10);
        $this->overrideSetting('board_text_max', 1000);
        $this->overrideSetting('board_category_min', 3);
        $this->overrideSetting('board_category_max', 50);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create();
        $this->board = Board::factory()->create(['parent_id' => 0, 'name' => 'Электроника', 'sort' => 1]);
    }

    public function testItemIsCreatedWithUploadedFiles(): void
    {
        $file = File::factory()->create([
            'relate_id'   => 0,
            'relate_type' => Item::$morphName,
            'path'        => '/uploads/boards/phone.jpg',
            'name'        => 'phone.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get('/items/create?bid=' . $this->board->id)
            ->assertOk()
            ->assertSee('name="category_id"', false);

        $this->actingAs($this->user)
            ->post('/items/create', $this->payload())
            ->assertSessionHas('success');

        $item = Item::query()->where('title', 'Продам телефон')->firstOrFail();

        $this->assertSame('+79001234567', $item->phone);
        $this->assertSame(5000, (int) $item->price);
        $this->assertSame($item->id, $file->fresh()->relate_id);
        $this->assertSame(1, $this->board->fresh()->count_items);
    }

    public function testClosedCategoryIsShownUnderCategory(): void
    {
        $this->board->update(['closed' => 1]);

        $this->actingAs($this->user)
            ->post('/items/create', $this->payload())
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('items', 0);
    }

    public function testFloodErrorIsShownUnderText(): void
    {
        $this->overrideSetting('floodstime', 60);

        $this->actingAs($this->user)->post('/items/create', $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($this->user)->post('/items/create', $this->payload())->assertSessionHasErrors('text');

        $this->assertDatabaseCount('items', 1);
    }

    public function testWrongPhoneIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/items/create', ['phone' => '123'] + $this->payload())
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('items', 0);
    }

    public function testCreationClosedBySetting(): void
    {
        $this->overrideSetting('boards_create', 0);

        $this->actingAs($this->user)
            ->post('/items/create', $this->payload())
            ->assertSee(__('board::boards.boards_closed'));

        $this->assertDatabaseCount('items', 0);
    }

    public function testGuestCannotCreate(): void
    {
        $this->get('/items/create')->assertForbidden();
        $this->post('/items/create', $this->payload())->assertForbidden();
    }

    public function testItemMovesToAnotherCategory(): void
    {
        $item = $this->createItem();
        $other = Board::factory()->create(['parent_id' => 0, 'name' => 'Одежда', 'sort' => 2]);

        $this->actingAs($this->user)
            ->post('/items/' . $item->id . '/edit', ['category_id' => $other->id] + $this->payload())
            ->assertRedirect(route('items.view', ['id' => $item->id]));

        $this->assertSame($other->id, $item->fresh()->board_id);
        $this->assertSame(0, $this->board->fresh()->count_items);
        $this->assertSame(1, $other->fresh()->count_items);
    }

    public function testEditErrorIsShownUnderCategory(): void
    {
        $item = $this->createItem();

        $this->actingAs($this->user)
            ->post('/items/' . $item->id . '/edit', ['category_id' => 999999] + $this->payload())
            ->assertSessionHasErrors('category_id');
    }

    public function testForeignItemIsNotEdited(): void
    {
        $item = $this->createItem();

        $this->actingAs(User::factory()->create())
            ->post('/items/' . $item->id . '/edit', $this->payload())
            ->assertForbidden();

        $this->assertSame('Старое объявление', $item->fresh()->title);
    }

    public function testAuthorClosesAndDeletesItem(): void
    {
        $item = $this->createItem();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->post('/items/' . $item->id . '/close')->assertForbidden();
        $this->actingAs($stranger)->delete('/items/' . $item->id . '/delete')->assertForbidden();

        $this->actingAs($this->user)
            ->post('/items/' . $item->id . '/close')
            ->assertSessionHas('success');

        $this->assertTrue($item->fresh()->expires_at->lte(now()));

        $this->actingAs($this->user)
            ->delete('/items/' . $item->id . '/delete')
            ->assertSessionHas('success');

        $this->assertNull($item->fresh());
    }

    public function testExpiredItemIsOpenToEveryone(): void
    {
        $item = $this->createItem(['expires_at' => now()->subDay()]);
        $banner = __('board::boards.item_not_active');

        $this->get($item->getViewUrl())->assertOk()->assertSee('Старое объявление')->assertSee($banner);

        // Раньше вошедший не-автор видел только сообщение, хотя гость видел запись
        $this->actingAs(User::factory()->create())
            ->get($item->getViewUrl())
            ->assertOk()
            ->assertSee('Старое объявление')
            ->assertSee($banner);
    }

    public function testUnpublishedItemIsNotSubtractedTwice(): void
    {
        $item = $this->createItem();

        $this->actingAs($this->user)->post('/items/' . $item->id . '/close');

        $this->assertSame(0, $this->board->fresh()->count_items);
        $this->assertFalse($item->fresh()->active);

        $this->artisan('board:deactivation')->assertSuccessful();

        $this->assertSame(0, $this->board->fresh()->count_items);
    }

    public function testRepublishedItemIsLiveAgain(): void
    {
        $item = $this->createItem(['expires_at' => now()->subDay()]);

        $this->artisan('board:deactivation')->assertSuccessful();
        $this->assertSame(0, $this->board->fresh()->count_items);

        $this->actingAs($this->user)->post('/items/' . $item->id . '/close');

        // Снова в ленте и статистике, которые берут только active
        $this->assertTrue($item->fresh()->active);
        $this->assertSame(1, $this->board->fresh()->count_items);

        // Истёк повторно — задача снова его вычитает
        $item->update(['expires_at' => now()->subMinute()]);
        $this->artisan('board:deactivation')->assertSuccessful();

        $this->assertSame(0, $this->board->fresh()->count_items);
    }

    public function testDeletingInactiveItemKeepsCounter(): void
    {
        $live = $this->createItem();
        $expired = $this->createItem(['expires_at' => now()->subDay()]);

        $this->artisan('board:deactivation')->assertSuccessful();
        $this->assertSame(1, $this->board->fresh()->count_items);

        $this->actingAs($this->user)->delete('/items/' . $expired->id . '/delete');
        $this->assertSame(1, $this->board->fresh()->count_items);

        $this->actingAs($this->user)->delete('/items/' . $live->id . '/delete');
        $this->assertSame(0, $this->board->fresh()->count_items);
    }

    public function testMovingInactiveItemKeepsCounters(): void
    {
        $item = $this->createItem(['expires_at' => now()->subDay()]);
        $other = Board::factory()->create(['parent_id' => 0, 'name' => 'Одежда', 'sort' => 2]);

        $this->artisan('board:deactivation')->assertSuccessful();

        $this->actingAs($this->user)
            ->post('/items/' . $item->id . '/edit', ['category_id' => $other->id] + $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame($other->id, $item->fresh()->board_id);
        $this->assertSame(0, $this->board->fresh()->count_items);
        $this->assertSame(0, $other->fresh()->count_items);
    }

    public function testGuestHasNoActivePage(): void
    {
        $this->get('/boards/active')->assertForbidden();
    }

    public function testAdminEditsItem(): void
    {
        $item = $this->createItem();

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->post('/admin/items/' . $item->id . '/edit', ['title' => 'Правка админа'] + $this->payload())
            ->assertRedirect(route('admin.items.edit', ['id' => $item->id]));

        $this->assertSame('Правка админа', $item->fresh()->title);
    }

    public function testBossManagesCategories(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->post('/admin/boards/create', ['name' => 'Мебель'])
            ->assertForbidden();

        $this->actingAs($boss)
            ->post('/admin/boards/create', ['name' => 'Ок'])
            ->assertSessionHasErrors('name');

        $this->actingAs($boss)
            ->post('/admin/boards/create', ['name' => 'Мебель'])
            ->assertSessionHas('success');

        $category = Board::query()->where('name', 'Мебель')->firstOrFail();

        $this->actingAs($boss)
            ->post('/admin/boards/' . $category->id . '/edit', ['name' => 'Мебель', 'parent' => $category->id])
            ->assertSessionHasErrors('parent');

        $this->actingAs($boss)
            ->post('/admin/boards/' . $category->id . '/edit', ['name' => 'Мебель и декор', 'parent' => $this->board->id, 'closed' => 1])
            ->assertRedirect(route('admin.boards.categories'));

        $category->refresh();
        $this->assertSame('Мебель и декор', $category->name);
        $this->assertSame($this->board->id, $category->parent_id);
        $this->assertTrue((bool) $category->closed);

        // Раздел с подразделом не удаляется
        $this->actingAs($boss)
            ->delete('/admin/boards/' . $this->board->id . '/delete')
            ->assertSessionHasErrors();

        $this->actingAs($boss)
            ->delete('/admin/boards/' . $category->id . '/delete')
            ->assertSessionHas('success');

        $this->assertNull($category->fresh());
    }

    private function payload(): array
    {
        return [
            'category_id' => $this->board->id,
            'title'       => 'Продам телефон',
            'text'        => '<p>Описание телефона для теста</p>',
            'price'       => 5000,
            'phone'       => '+7 (900) 123-45-67',
        ];
    }

    private function createItem(array $attributes = []): Item
    {
        $item = Item::factory()->create($attributes + [
            'board_id'   => $this->board->id,
            'title'      => 'Старое объявление',
            'text'       => 'Описание старого объявления',
            'user_id'    => $this->user->id,
            'price'      => 100,
            'phone'      => '',
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->board->increment('count_items');

        return $item;
    }
}
