<?php

namespace Modules\Advert\Tests\Feature;

use App\Models\User;
use App\Support\Hook;
use Illuminate\Support\Facades\Cache;
use Modules\Advert\Models\Advert;
use Tests\ModuleTestCase;

class AdvertTest extends ModuleTestCase
{
    protected string $moduleName = 'Advert';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('rekusershow', 5);
        $this->overrideSetting('rekuserpost', 10);
        $this->overrideSetting('rekusertotal', 10);
        $this->overrideSetting('rekuserpoint', 10);
        $this->overrideSetting('rekuserprice', 100);
        $this->overrideSetting('rekuseroptprice', 50);
        $this->overrideSetting('rekusertime', 24);
        // Капча проверяется по фразе из сессии, тест подставляет её сам
        $this->overrideSetting('captcha_type', 'graphical');
        // Ежедневный бонус ядра начисляется прямо в запросе и сбивал бы счёт денег
        $this->overrideSetting('bonusmoney', 0);

        Advert::query()->delete();
        Cache::forget('adverts');
        Cache::forget('adminAdverts');

        $this->user = User::factory()->create(['point' => 100, 'money' => 1000]);
    }

    public function testListShowsLiveAdverts(): void
    {
        $this->advert(['name' => 'Живая реклама']);
        $this->advert(['name' => 'Просроченная', 'deleted_at' => now()->subDay()]);

        $this->actingAs($this->user)
            ->get('/adverts')
            ->assertOk()
            ->assertSee('Живая реклама')
            ->assertDontSee('Просроченная');
    }

    public function testAdvertIsBoughtForMoney(): void
    {
        $this->actingAs($this->user)
            ->withSession(['protect' => 'ключ'])
            ->post('/adverts/create', [
                'site'    => 'https://visavi.net',
                'name'    => 'Мой сайт',
                'protect' => 'ключ',
            ])
            ->assertRedirect('adverts');

        $this->assertDatabaseHas('adverts', [
            'user_id' => $this->user->id,
            'name'    => 'Мой сайт',
            'type'    => Advert::TYPE_USER,
        ]);

        $this->assertSame(900, $this->user->fresh()->money);
    }

    public function testOptionsRaisePrice(): void
    {
        $this->actingAs($this->user)
            ->withSession(['protect' => 'ключ'])
            ->post('/adverts/create', [
                'site'    => 'https://visavi.net',
                'name'    => 'Мой сайт',
                'color'   => '#ff0000',
                'bold'    => 1,
                'protect' => 'ключ',
            ])
            ->assertRedirect('adverts');

        // Цвет и жирность добавляют к цене по rekuseroptprice
        $this->assertSame(800, $this->user->fresh()->money);
    }

    public function testPurchaseNeedsMoney(): void
    {
        $this->user->update(['money' => 10]);

        $this->actingAs($this->user)
            ->post('/adverts/create', [
                'site' => 'https://visavi.net',
                'name' => 'Мой сайт',
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('adverts', 0);
    }

    public function testPurchaseNeedsPoints(): void
    {
        $this->user->update(['point' => 1]);

        $this->actingAs($this->user)
            ->get('/adverts/create')
            ->assertSee(__('advert::adverts.advert_point', ['point' => plural(50, setting('scorename'))]));

        $this->assertDatabaseCount('adverts', 0);
    }

    public function testInvalidSiteIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/adverts/create', [
                'site' => 'без протокола',
                'name' => 'Мой сайт',
            ])
            ->assertSessionHasErrors('site');

        $this->assertDatabaseCount('adverts', 0);
    }

    public function testSecondAdvertIsRejected(): void
    {
        $this->advert(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get('/adverts/create')
            ->assertSee(__('advert::adverts.advert_already_posted'));
    }

    public function testNoSeatsLeft(): void
    {
        $this->overrideSetting('rekusertotal', 1);
        $this->advert();

        $this->actingAs($this->user)
            ->get('/adverts/create')
            ->assertSee(__('advert::adverts.advert_not_seats'));
    }

    public function testSectionIsClosedWithoutSetting(): void
    {
        $this->overrideSetting('rekusershow', 0);

        $this->actingAs($this->user)
            ->get('/adverts')
            ->assertSee(__('advert::adverts.advert_closed'));
    }

    public function testGuestIsRejected(): void
    {
        $this->get('/adverts')->assertForbidden();
    }

    public function testHookShowsLinksInLayout(): void
    {
        $this->advert(['name' => 'Реклама в шапке']);

        // Блок в шапке ядро выводит хуком advertTop
        $this->assertTrue(Hook::has('advertTop'));
        $this->assertStringContainsString('Реклама в шапке', (string) Hook::call('advertTop'));
    }

    public function testExpiredAdvertIsNotShownByHook(): void
    {
        $this->advert(['name' => 'Просроченная', 'deleted_at' => now()->subDay()]);

        $this->assertStringNotContainsString('Просроченная', (string) Hook::call('advertTop'));
    }

    public function testAdvertsAreRemovedWithUser(): void
    {
        $this->advert(['user_id' => $this->user->id]);

        // Чистит хук onDeleteUser
        $this->user->delete();

        $this->assertDatabaseCount('adverts', 0);
    }

    private function advert(array $attributes = []): Advert
    {
        return Advert::query()->create([
            'site'       => 'https://visavi.net',
            'name'       => 'Реклама',
            'type'       => Advert::TYPE_USER,
            'user_id'    => User::factory()->create()->id,
            'created_at' => now(),
            'deleted_at' => now()->addDay(),
            ...$attributes,
        ]);
    }
}
