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

        $this->overrideSetting('rekuseractive', 1);
        $this->overrideSetting('rekusershow', 5);
        $this->overrideSetting('rekuserpost', 10);
        $this->overrideSetting('rekuserpoint', 10);
        $this->overrideSetting('rekuserprice', 100);
        $this->overrideSetting('rekuseroptprice', 50);
        $this->overrideSetting('rekusertime', 24);
        $this->overrideSetting('rekadmintime', 7);
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
            ->withSession(['protect' => 'ключ'])
            ->post('/adverts/create', [
                'site'    => 'https://visavi.net',
                'name'    => 'Мой сайт',
                'protect' => 'ключ',
            ])
            ->assertSessionHasErrors('money');

        $this->assertDatabaseCount('adverts', 0);
    }

    public function testCaptchaIsChecked(): void
    {
        $this->actingAs($this->user)
            ->withSession(['protect' => 'ключ'])
            ->post('/adverts/create', [
                'site'    => 'https://visavi.net',
                'name'    => 'Мой сайт',
                'protect' => 'другое',
            ])
            ->assertSessionHasErrors('protect');

        $this->assertDatabaseCount('adverts', 0);
        $this->assertSame(1000, $this->user->fresh()->money);
    }

    public function testWrongColorIsRejected(): void
    {
        // Старая проверка цвета пропускала буквы дальше f и несколько решёток
        foreach (['#zzzzzz', '##ff0000'] as $color) {
            $this->actingAs($this->user)
                ->withSession(['protect' => 'ключ'])
                ->post('/adverts/create', [
                    'site'    => 'https://visavi.net',
                    'name'    => 'Мой сайт',
                    'color'   => $color,
                    'protect' => 'ключ',
                ])
                ->assertSessionHasErrors('color');
        }

        $this->assertDatabaseCount('adverts', 0);
    }

    public function testAdminEditsAndDeletesUserLinks(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);
        $link = $this->advert();

        $this->actingAs($admin)
            ->get('/admin/adverts/edit/' . $link->id)
            ->assertOk()
            ->assertSee('maxlength="100"', false);

        $this->actingAs($admin)
            ->post('/admin/adverts/edit/' . $link->id . '?page=2', ['site' => 'https://visavi.net', 'name' => 'Ой'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post('/admin/adverts/edit/' . $link->id . '?page=2', [
                'site'  => 'https://rotor.visavi.net',
                'name'  => 'Новое имя',
                'color' => '#00AA00',
                'bold'  => 1,
            ])
            ->assertRedirect('admin/adverts?page=2');

        $link->refresh();
        $this->assertSame('Новое имя', $link->name);
        $this->assertSame('#00AA00', $link->color);
        $this->assertTrue((bool) $link->bold);

        $this->actingAs($admin)
            ->post('/admin/adverts/delete?page=2', ['del' => []])
            ->assertSessionHasErrors('del');

        $this->actingAs($admin)
            ->post('/admin/adverts/delete?page=2', ['del' => [$link->id]])
            ->assertRedirect('admin/adverts?page=2');

        $this->assertNull($link->fresh());
    }

    public function testAdminAdvertNameErrorIsShownUnderName(): void
    {
        $admin = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($admin)
            ->from('/admin/admin-adverts')
            ->followingRedirects()
            ->post('/admin/admin-adverts', ['site' => 'https://example.com', 'name' => 'Ой'])
            ->assertSee('form-control is-invalid" id="name"', false)
            ->assertDontSee('form-control is-invalid" id="site"', false);
    }

    public function testPurchaseNeedsPoints(): void
    {
        $this->user->update(['point' => 1]);

        $this->actingAs($this->user)
            ->get('/adverts/create')
            ->assertSee(__('advert::adverts.advert_point', ['point' => plural(10, setting('scorename'))]));

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

    public function testAdminAdvertTermComesFromSetting(): void
    {
        $this->overrideSetting('rekadmintime', 3);
        $admin = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($admin)
            ->post('/admin/admin-adverts', [
                'site' => 'https://example.com',
                'name' => 'Админская ссылка',
            ])
            ->assertRedirect('admin/admin-adverts');

        $advert = Advert::query()->where('type', Advert::TYPE_ADMIN)->firstOrFail();

        $this->assertSame(now()->addDays(3)->format('Y-m-d H:i'), $advert->deleted_at->format('Y-m-d H:i'));
    }

    public function testAdminAdvertsAreSeparatePerAdmin(): void
    {
        $first = User::factory()->create(['level' => User::BOSS]);
        $second = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($first)
            ->post('/admin/admin-adverts', ['site' => 'https://first.test', 'name' => 'Первая ссылка'])
            ->assertRedirect('admin/admin-adverts');

        $this->actingAs($second)
            ->post('/admin/admin-adverts', ['site' => 'https://second.test', 'name' => 'Вторая ссылка'])
            ->assertRedirect('admin/admin-adverts');

        // Второй админ не должен перетирать рекламу первого
        $this->assertDatabaseHas('adverts', ['user_id' => $first->id, 'name' => 'Первая ссылка']);
        $this->assertDatabaseHas('adverts', ['user_id' => $second->id, 'name' => 'Вторая ссылка']);
    }

    public function testSectionIsClosedWithoutSetting(): void
    {
        $this->overrideSetting('rekuseractive', 0);

        $this->actingAs($this->user)
            ->get('/adverts')
            ->assertSee(__('advert::adverts.advert_closed'));
    }

    public function testDisabledSettingHidesBlockInLayout(): void
    {
        $this->advert(['name' => 'Реклама в шапке']);
        $this->overrideSetting('rekuseractive', 0);
        Cache::forget('adverts');

        // Блок с предложением разместить рекламу виден только авторизованным
        $this->actingAs($this->user);

        $html = (string) Hook::call('advertTop');

        $this->assertStringNotContainsString('Реклама в шапке', $html);
        $this->assertStringNotContainsString(__('advert::adverts.create_advert'), $html);
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

    public function testOwnerEditsAdvertWithoutProlonging(): void
    {
        $expires = now()->addHours(5)->startOfSecond();
        $link = $this->advert([
            'user_id'    => $this->user->id,
            'color'      => '#ff0000',
            'bold'       => true,
            'deleted_at' => $expires,
        ]);

        // Блок рекламы уже закеширован со старым названием
        Hook::call('advertTop');

        $this->actingAs($this->user)
            ->get('/adverts')
            ->assertSee('/adverts/edit/' . $link->id);

        $this->actingAs($this->user)
            ->get('/adverts/edit/' . $link->id)
            ->assertOk()
            ->assertSee('Реклама');

        // Оплаченный цвет можно сменить на другой, жирность правка не трогает
        $this->actingAs($this->user)
            ->post('/adverts/edit/' . $link->id, [
                'site'  => 'https://rotor.visavi.net',
                'name'  => 'Новое название',
                'color' => '#00ff00',
                'bold'  => 0,
            ])
            ->assertRedirect('adverts')
            ->assertSessionHas('success');

        $link->refresh();
        $this->assertSame('https://rotor.visavi.net', $link->site);
        $this->assertSame('Новое название', $link->name);
        $this->assertSame('#00ff00', $link->color);
        $this->assertTrue($link->bold);
        $this->assertTrue($expires->equalTo($link->deleted_at));
        $this->assertSame(1000, $this->user->fresh()->money);

        $this->assertStringContainsString('Новое название', (string) Hook::call('advertTop'));
    }

    public function testColorChangesOnlyWhenPaid(): void
    {
        $plain = $this->advert(['user_id' => $this->user->id]);

        // Цвет не покупали — бесплатно его не добавить
        $this->actingAs($this->user)
            ->post('/adverts/edit/' . $plain->id, ['site' => 'https://visavi.net', 'name' => 'Реклама', 'color' => '#00ff00', 'bold' => 1])
            ->assertRedirect('adverts');

        $this->assertNull($plain->fresh()->color);
        $this->assertFalse($plain->fresh()->bold);

        // Пустое поле не стирает оплаченный цвет
        $paid = $this->advert(['user_id' => $this->user->id, 'color' => '#ff0000']);

        $this->actingAs($this->user)
            ->post('/adverts/edit/' . $paid->id, ['site' => 'https://visavi.net', 'name' => 'Реклама', 'color' => ''])
            ->assertRedirect('adverts');

        $this->assertSame('#ff0000', $paid->fresh()->color);

        $this->actingAs($this->user)
            ->post('/adverts/edit/' . $paid->id, ['site' => 'https://visavi.net', 'name' => 'Реклама', 'color' => 'красный'])
            ->assertSessionHasErrors('color');
    }

    public function testOwnerEditIsValidated(): void
    {
        $link = $this->advert(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post('/adverts/edit/' . $link->id, ['site' => 'не адрес', 'name' => 'Ой'])
            ->assertSessionHasErrors(['site', 'name']);

        $this->assertSame('https://visavi.net', $link->fresh()->site);
    }

    public function testOnlyOwnerEditsLiveAdvert(): void
    {
        $stranger = $this->advert(['name' => 'Чужая']);
        $expired = $this->advert(['user_id' => $this->user->id, 'deleted_at' => now()->subHour()]);

        $this->actingAs($this->user)
            ->get('/adverts')
            ->assertDontSee('/adverts/edit/' . $stranger->id);

        foreach ([$stranger, $expired] as $link) {
            $this->actingAs($this->user)
                ->get('/adverts/edit/' . $link->id)
                ->assertNotFound();

            $this->actingAs($this->user)
                ->post('/adverts/edit/' . $link->id, ['site' => 'https://rotor.visavi.net', 'name' => 'Захват'])
                ->assertNotFound();
        }

        $this->assertSame('Чужая', $stranger->fresh()->name);
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
        return Advert::factory()->create([
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
