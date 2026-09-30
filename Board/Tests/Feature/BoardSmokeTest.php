<?php

namespace Modules\Board\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;
use Tests\ModuleTestCase;

class BoardSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Board';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Item::$morphName => Item::class]);

        $this->user = User::factory()->create();
    }

    public function testIndex(): void
    {
        $this->get(route('boards.index'))->assertOk();
    }

    public function testCategory(): void
    {
        $board = Board::query()->create(['name' => 'Test board']);

        $this->get(route('boards.index', ['id' => $board->id]))->assertOk();
    }

    public function testSettingsUpdate(): void
    {
        $admin = User::factory()->boss()->create();

        $response = $this->actingAs($admin)->post(route('board.settings.update'), [
            'sets' => ['board_create_user_point' => '15'],
        ]);

        $response->assertRedirect(route('board.settings'));
        $this->assertDatabaseHas('settings', ['name' => 'board_create_user_point', 'value' => '15']);
    }

    public function testSettingsUpdateEmpty(): void
    {
        $admin = User::factory()->boss()->create();

        $this->actingAs($admin)
            ->from(route('boards.index'))
            ->post(route('board.settings.update'))
            ->assertRedirect(route('boards.index'));

        self::assertSame(__('settings.settings_empty'), session('danger'));
    }

    public function testItem(): void
    {
        $board = Board::query()->create(['name' => 'Test board']);

        $item = Item::query()->create([
            'board_id'   => $board->id,
            'title'      => 'Test item',
            'text'       => 'Test item text',
            'user_id'    => $this->user->id,
            'created_at' => now()->timestamp,
            'updated_at' => now()->timestamp,
            'expires_at' => now()->addDay()->timestamp,
        ]);

        $this->get($item->getViewUrl())->assertOk();
    }

    public function testRenewedItemShowsBothDates(): void
    {
        $item = $this->createItem('');
        $renewed = __('board::boards.renewed');

        $this->get($item->getViewUrl())->assertOk()->assertDontSee($renewed);

        $item->update(['created_at' => now()->subMonth(), 'updated_at' => now()]);

        $this->get($item->getViewUrl())
            ->assertOk()
            ->assertSee(dateFixed($item->created_at))
            ->assertSee($renewed . ' ' . dateFixed($item->updated_at));
    }

    public function testPhoneHiddenUntilClick(): void
    {
        $item = $this->createItem('+79121234567');

        $this->get($item->getViewUrl())
            ->assertOk()
            ->assertDontSee('1234567')
            ->assertSee(route('items.phone', ['id' => $item->id]));

        $this->get(route('boards.index'))
            ->assertOk()
            ->assertDontSee('1234567');

        $this->postJson(route('items.phone', ['id' => $item->id]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'tel:+79121234567'));
    }

    public function testPhoneMissing(): void
    {
        $item = $this->createItem('');

        $this->postJson(route('items.phone', ['id' => $item->id]))
            ->assertOk()
            ->assertJson(['success' => false]);
    }

    public function testCityFilter(): void
    {
        $this->createItem('', ['title' => 'Moscow item', 'city' => 'Москва']);
        $this->createItem('', ['title' => 'Kazan item', 'city' => 'Казань']);

        // Регистр в адресе не важен: сравнение в MySQL без учёта регистра
        $this->get(route('boards.index', ['city' => 'москва']))
            ->assertOk()
            ->assertSee('Moscow item')
            ->assertDontSee('Kazan item');
    }

    public function testCitySuggestions(): void
    {
        $this->createItem('', ['city' => 'Москва']);
        $this->createItem('', ['city' => 'Москва']);
        $this->createItem('', ['city' => 'Мозырь']);
        // Истёкшие объявления тоже дают подсказки
        $this->createItem('', ['city' => 'Мончегорск', 'active' => false, 'expires_at' => now()->subDay()]);
        $this->createItem('', ['city' => 'Казань']);

        // Города из анкет: складываются с объявлениями, одиночный вариант отсекается
        User::factory()->count(2)->create(['city' => 'москва']);
        User::factory()->count(2)->create(['city' => 'Моршанск']);
        User::factory()->create(['city' => 'Мокрое']);

        $this->getJson(route('boards.cities', ['query' => 'М']))
            ->assertOk()
            ->assertExactJson([]);

        // Частые города первыми, написание — из объявлений
        $this->getJson(route('boards.cities', ['query' => 'мо']))
            ->assertOk()
            ->assertExactJson([
                ['value' => 'Москва', 'label' => 'Москва'],
                ['value' => 'Моршанск', 'label' => 'Моршанск'],
                ['value' => 'Мозырь', 'label' => 'Мозырь'],
                ['value' => 'Мончегорск', 'label' => 'Мончегорск'],
            ]);

        // % в запросе ищется буквально, а не как шаблон
        $this->getJson(route('boards.cities', ['query' => 'М%']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function testCreateFormTakesCityFromProfile(): void
    {
        $this->overrideSetting('boards_create', 1);
        Board::query()->create(['name' => 'Test board']);

        $this->user->update(['city' => 'Казань']);

        $this->actingAs($this->user)
            ->get(route('items.create'))
            ->assertOk()
            ->assertSee('<option value="Казань" selected>Казань</option>', false);
    }

    public function testMessengersShownWithPhone(): void
    {
        $item = $this->createItem('+79121234567', ['messengers' => ['max', 'whatsapp', 'unknown']]);

        // Неизвестный ключ отброшен, порядок — как в списке мессенджеров
        self::assertSame(['whatsapp', 'max'], $item->fresh()->messengers);

        $html = $this->postJson(route('items.phone', ['id' => $item->id]))->json('html');

        self::assertStringContainsString('https://wa.me/79121234567', $html);
        self::assertStringContainsString(__('board::boards.messenger_has_number', ['name' => 'MAX']), $html);
        self::assertStringNotContainsString('t.me', $html);
    }

    public function testDisabledMessengerIsHidden(): void
    {
        $this->overrideSetting('board_messenger_whatsapp', 0);

        $item = $this->createItem('+79121234567', ['messengers' => ['whatsapp', 'telegram']]);

        $html = $this->postJson(route('items.phone', ['id' => $item->id]))->json('html');

        self::assertStringNotContainsString('wa.me', $html);
        self::assertStringContainsString('https://t.me/+79121234567', $html);
        // Отметка не пропала: включат снова — вернётся
        self::assertSame(['whatsapp', 'telegram'], $item->fresh()->messengers);
    }

    public function testMessengersClearedWithoutPhone(): void
    {
        $item = $this->createItem('+79121234567', ['messengers' => ['whatsapp']]);

        $item->update(['phone' => '']);

        self::assertSame([], $item->fresh()->messengers);
    }

    public function testExpiresShownToAuthorOnly(): void
    {
        $item = $this->createItem('');
        $expires = __('board::boards.expires_in');

        $this->get($item->getViewUrl())->assertOk()->assertDontSee($expires);

        $this->actingAs(User::factory()->create())
            ->get($item->getViewUrl())
            ->assertDontSee($expires);

        $this->actingAs($this->user)
            ->get($item->getViewUrl())
            ->assertSee($expires);
    }

    public function testEditIsForAuthorOnly(): void
    {
        $item = $this->createItem('');

        $this->actingAs($this->user)->get(route('items.edit', ['id' => $item->id]))->assertOk();

        $this->actingAs(User::factory()->create())
            ->get(route('items.edit', ['id' => $item->id]))
            ->assertForbidden();

        $this->actingAs(User::factory()->boss()->create())
            ->get(route('items.edit', ['id' => $item->id]))
            ->assertRedirect(route('admin.items.edit', ['id' => $item->id]));
    }

    private function createItem(string $phone, array $attributes = []): Item
    {
        $board = Board::query()->create(['name' => 'Test board']);

        return Item::query()->create($attributes + [
            'board_id'   => $board->id,
            'title'      => 'Test item',
            'text'       => 'Test item text',
            'phone'      => $phone,
            'user_id'    => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addDay(),
        ]);
    }
}
