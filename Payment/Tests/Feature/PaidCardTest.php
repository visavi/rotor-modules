<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use App\Support\Hook;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Payment\Console\CleanCardImages;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;
use Modules\Payment\Services\CardImageService;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\Services\YooKassaService;
use Tests\ModuleTestCase;

class PaidCardTest extends ModuleTestCase
{
    protected string $moduleName = 'Payment';

    private array $imagesBefore = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Незафейканный запрос — ошибка теста, а не молчаливый поход в реальную сеть
        Http::preventStrayRequests();

        config(['payment' => require base_path('modules/Payment/config.php')]);

        // Каталог картинок общий с сайтом: после теста удаляются только появившиеся в нём файлы
        $this->imagesBefore = $this->cardImages();

        $this->setSettings([
            'payment_price_card_sidebar'     => 20,
            'payment_price_card_feed_middle' => 30,
            'payment_card_limit'             => 2,
        ]);
    }

    protected function tearDown(): void
    {
        File::delete(array_diff($this->cardImages(), $this->imagesBefore));

        parent::tearDown();
    }

    /**
     * Файлы в каталоге картинок карточек
     */
    private function cardImages(): array
    {
        return glob(public_path(CardImageService::path() . '/*')) ?: [];
    }

    /**
     * Пишет настройки (SettingSeeder в тестах очищает таблицу settings)
     */
    private function setSettings(array $settings): void
    {
        foreach ($settings as $name => $value) {
            Setting::query()->updateOrInsert(['name' => $name], ['value' => $value]);
        }

        clearCache('settings');
    }

    /**
     * Создаёт активную карточку
     */
    private function createCard(?User $user = null, array $attributes = []): PaidAdvert
    {
        return PaidAdvert::factory()->create(array_merge([
            'place'      => PaidAdvert::CARD_SIDEBAR,
            'url'        => 'https://example.com',
            'names'      => ['Заголовок карточки'],
            'text'       => 'Текст карточки',
            'user_id'    => $user?->id,
            'created_at' => now(),
            'deleted_at' => now()->addDays(10),
        ], $attributes));
    }

    public function testCardPlacesSkipZeroPrice(): void
    {
        $this->setSettings(['payment_price_card_feed_middle' => 0]);

        $places = app(PaymentService::class)->getCardPlaces();

        $this->assertSame([PaidAdvert::CARD_SIDEBAR], array_keys($places));
        $this->assertSame(20, $places[PaidAdvert::CARD_SIDEBAR]['price']);
    }

    public function testCardPlaceTakenAndFreeAt(): void
    {
        $first = $this->createCard(attributes: ['deleted_at' => now()->addDays(3)]);
        $this->createCard(attributes: ['deleted_at' => now()->addDays(5)]);
        $this->createCard(attributes: ['deleted_at' => now()->subDay()]);

        $service = app(PaymentService::class);
        $place = $service->getCardPlaces()[PaidAdvert::CARD_SIDEBAR];

        $this->assertSame(2, $place['taken']);
        $this->assertSame(2, $place['limit']);
        $this->assertTrue($first->fresh()->deleted_at->equalTo($place['freeAt']));
        $this->assertFalse($service->isCardPlaceAvailable(PaidAdvert::CARD_SIDEBAR));
        $this->assertTrue($service->isCardPlaceAvailable(PaidAdvert::CARD_FEED_MIDDLE));
    }

    /**
     * Данные заказа карточки (как после calculate)
     */
    private function cardData(array $attributes = []): array
    {
        return array_merge([
            'type'        => Order::TYPE_CARD,
            'place'       => PaidAdvert::CARD_FEED_MIDDLE,
            'url'         => 'https://example.com',
            'title'       => 'Хостинг для сайтов',
            'text'        => 'Быстрые серверы и поддержка',
            'image'       => null,
            'term'        => 5,
            'email'       => 'test@example.com',
            'description' => 'Рекламная карточка',
            'prices'      => ['total' => 150, 'place' => 150],
            'expires_at'  => now()->addHour()->timestamp,
        ], $attributes);
    }

    public function testCardIndexGuestSeesLoginPrompt(): void
    {
        $this->get('/payments/card')
            ->assertOk()
            ->assertSee(__('payment::payments.cards.login_required'))
            ->assertDontSee('/payments/card/calculate');
    }

    public function testCardCalculateGuestForbidden(): void
    {
        $this->post('/payments/card/calculate', [])->assertForbidden();
    }

    public function testCardPayGuestRejected(): void
    {
        $this->post('/payments/pay', ['data' => Crypt::encrypt($this->cardData())])
            ->assertRedirect('/payments/card');

        $this->assertDatabaseCount('orders', 0);
    }

    public function testCardPayRejectsFullPlace(): void
    {
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE]);
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE]);

        $this->actingAs(User::factory()->create())
            ->post('/payments/pay', ['data' => Crypt::encrypt($this->cardData())])
            ->assertRedirect('/payments/card')
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('orders', 0);
    }

    public function testCardPayRejectsChangedPrice(): void
    {
        $this->setSettings(['payment_price_card_feed_middle' => 40]);

        $this->actingAs(User::factory()->create())
            ->post('/payments/pay', ['data' => Crypt::encrypt($this->cardData())])
            ->assertRedirect('/payments/card')
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('orders', 0);
    }

    public function testCardPayRejectsExpiredPayload(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/payments/pay', ['data' => Crypt::encrypt($this->cardData(['expires_at' => now()->subMinute()->timestamp]))])
            ->assertRedirect('/payments/card')
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('orders', 0);
    }

    public function testCardCalculateRejectsHugeImage(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/payments/card/calculate', [
                'place' => PaidAdvert::CARD_FEED_MIDDLE,
                'title' => 'Хостинг для сайтов',
                'url'   => 'https://example.com',
                'image' => UploadedFile::fake()->image('huge.png', 4100, 300),
                'term'  => 5,
                'email' => 'test@example.com',
            ])
            ->assertSessionHasErrors(['image']);
    }

    public function testExpiredCardHiddenFromCache(): void
    {
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE, 'deleted_at' => now()->addMinutes(10)]);

        $this->assertStringContainsString('Заголовок карточки', Hook::call('feedAdvert', 'middle'));

        $this->travel(11)->minutes();

        $this->assertStringNotContainsString('Заголовок карточки', Hook::call('feedAdvert', 'middle'));
    }

    public function testDeleteKeepsImageSharedWithOtherCard(): void
    {
        $image = $this->makeImageFile('shared');
        $card = $this->createCard(attributes: ['image' => $image]);
        $this->createCard(attributes: ['image' => $image]);

        $this->actingAs(User::factory()->boss()->create())
            ->delete('/admin/paid-adverts/delete/' . $card->id)
            ->assertRedirect();

        $this->assertFileExists(public_path($image));
    }

    public function testCardIndexListsOnlyPricedPlaces(): void
    {
        $this->setSettings(['payment_price_card_feed_middle' => 0]);

        $this->actingAs(User::factory()->create())
            ->get('/payments/card')
            ->assertOk()
            ->assertSee(__('payment::payments.cards.card_sidebar'))
            ->assertDontSee(__('payment::payments.cards.card_feed_middle'));
    }

    public function testCardCalculateWithImage(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post('/payments/card/calculate', [
                'place' => PaidAdvert::CARD_FEED_MIDDLE,
                'title' => 'Хостинг для сайтов',
                'text'  => 'Быстрые серверы и поддержка',
                'url'   => 'https://example.com',
                'image' => UploadedFile::fake()->image('banner.jpg', 1000, 700),
                'term'  => 5,
                'email' => 'test@example.com',
            ]);

        $response->assertOk()->assertSee('150');

        $pattern = '#' . preg_quote(CardImageService::path(), '#') . '/[\w.-]+\.webp#';
        $html = (string) $response->getContent();
        $this->assertMatchesRegularExpression($pattern, $html);
        preg_match($pattern, $html, $match);

        $file = public_path($match[0]);
        $this->assertFileExists($file);
        $this->assertSame([600, 600], array_slice(getimagesize($file) ?: [], 0, 2));

        unlink($file);
    }

    public function testCardCalculateValidation(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/payments/card/calculate', [
                'place' => PaidAdvert::CARD_FEED_MIDDLE,
                'title' => 'Хос',
                'text'  => str_repeat('a', 201),
                'url'   => 'not-a-url',
                'image' => UploadedFile::fake()->image('small.jpg', 200, 200),
                'term'  => 5,
                'email' => 'test@example.com',
            ])
            ->assertRedirect('/payments/card')
            ->assertSessionHasErrors(['title', 'text', 'url', 'image']);
    }

    public function testCardCalculateArrayFieldsFailValidation(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/payments/card/calculate', [
                'place' => [PaidAdvert::CARD_FEED_MIDDLE],
                'email' => ['test@example.com'],
            ])
            ->assertSessionHasErrors(['place', 'email']);
    }

    public function testCardUrlOnlyHttp(): void
    {
        // Правило url без схем пропускает ftp://, file:// и десятки других — ссылка на сайт только http(s)
        $this->actingAs(User::factory()->create())
            ->post('/payments/card/calculate', [
                'place' => PaidAdvert::CARD_FEED_MIDDLE,
                'url'   => 'ftp://example.com/file',
            ])
            ->assertSessionHasErrors('url');
    }

    public function testCardCalculatePlaceFull(): void
    {
        $this->createCard();
        $this->createCard();

        $this->actingAs(User::factory()->create())
            ->post('/payments/card/calculate', [
                'place' => PaidAdvert::CARD_SIDEBAR,
                'title' => 'Хостинг для сайтов',
                'url'   => 'https://example.com',
                'term'  => 5,
                'email' => 'test@example.com',
            ])
            ->assertSessionHasErrors(['place']);
    }

    public function testCardPayCreatesCardOrder(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'id'           => 'pay-card',
                'status'       => YooKassaService::PENDING,
                'confirmation' => ['confirmation_url' => 'https://yookassa.test/confirm'],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/payments/pay', ['data' => Crypt::encrypt($this->cardData())])
            ->assertRedirect('https://yookassa.test/confirm');

        $this->assertDatabaseHas('orders', ['payment_id' => 'pay-card', 'type' => Order::TYPE_CARD, 'amount' => 150]);
    }

    public function testWebhookCreatesCardAndNotifiesBoss(): void
    {
        $boss = User::factory()->boss()->create(['language' => 'ru']);
        $buyer = User::factory()->create();

        Order::factory()->create([
            'user_id'     => $buyer->id,
            'type'        => Order::TYPE_CARD,
            'amount'      => 150,
            'currency'    => 'RUB',
            'token'       => 'test-token-card-123456',
            'payment_id'  => 'pay-card',
            'status'      => YooKassaService::PENDING,
            'email'       => 'test@example.com',
            'description' => 'Рекламная карточка',
            'data'        => $this->cardData(),
        ]);

        Http::fake([
            'api.yookassa.ru/v3/payments/pay-card' => Http::response([
                'id'     => 'pay-card',
                'status' => YooKassaService::SUCCEEDED,
            ]),
        ]);

        $webhook = ['event' => 'payment.succeeded', 'object' => ['id' => 'pay-card']];
        $this->post('/payments/webhook', $webhook)->assertOk();
        $this->post('/payments/webhook', $webhook)->assertOk();

        $card = PaidAdvert::query()->sole();
        $this->assertSame(PaidAdvert::CARD_FEED_MIDDLE, $card->place);
        $this->assertSame(['Хостинг для сайтов'], $card->names);
        $this->assertSame('Быстрые серверы и поддержка', $card->text);
        $this->assertSame($buyer->id, $card->user_id);
        $this->assertTrue($card->deleted_at->between(now()->addDays(5)->subMinute(), now()->addDays(5)->addMinute()));

        $messages = Message::query()->where('user_id', $boss->id)->get();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('Хостинг для сайтов', $messages[0]->text);
        $this->assertStringContainsString('/admin/paid-adverts/edit-card/' . $card->id, $messages[0]->text);
    }

    public function testSidebarShowsCard(): void
    {
        $this->createCard(attributes: ['image' => '/uploads/paid-adverts/test.webp']);

        $html = Hook::call('sidebarFooterEnd');

        $this->assertStringContainsString('Заголовок карточки', $html);
        $this->assertStringContainsString('/uploads/paid-adverts/test.webp', $html);
        $this->assertStringNotContainsString(__('payment::payments.cards.your_card_here'), $html);
    }

    public function testSidebarShowsStubWithoutCards(): void
    {
        $html = Hook::call('sidebarFooterEnd');

        $this->assertStringContainsString(__('payment::payments.cards.your_card_here'), $html);
        $this->assertStringContainsString('/payments/card', $html);
    }

    public function testFeedHookShowsCardWithSponsoredLink(): void
    {
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE, 'image' => '/uploads/paid-adverts/feed.webp']);

        $html = Hook::call('feedAdvert', 'middle');

        $this->assertStringContainsString('rel="nofollow sponsored"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString(__('payment::payments.cards.advert'), $html);
        $this->assertStringContainsString('/uploads/paid-adverts/feed.webp', $html);
    }

    public function testFeedHookShowsEachPlaceAtItsPoint(): void
    {
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_START, 'names' => ['Баннер в начале']]);
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_END, 'names' => ['Баннер в конце']]);

        $this->assertStringContainsString('Баннер в начале', Hook::call('feedAdvert', 'start'));
        $this->assertStringNotContainsString('Баннер', Hook::call('feedAdvert', 'middle'));
        $this->assertStringContainsString('Баннер в конце', Hook::call('feedAdvert', 'end'));
        $this->assertStringNotContainsString('Баннер в начале', Hook::call('feedAdvert', 'end'));
    }

    public function testExpiredCardHidden(): void
    {
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE, 'deleted_at' => now()->subMinute()]);

        $this->assertStringNotContainsString('Заголовок карточки', Hook::call('feedAdvert', 'middle'));
    }

    public function testCardShownAfterPriceZeroed(): void
    {
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE]);
        $this->setSettings(['payment_price_card_feed_middle' => 0]);

        $this->assertStringContainsString('Заголовок карточки', Hook::call('feedAdvert', 'middle'));
    }

    public function testCardTitleEscaped(): void
    {
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE, 'names' => ['<script>x</script>'], 'text' => '<b>t</b>']);

        $html = Hook::call('feedAdvert', 'middle');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
    }

    public function testCardTextKeepsUpToThreeLines(): void
    {
        $this->createCard(attributes: [
            'place' => PaidAdvert::CARD_FEED_MIDDLE,
            'text'  => "Первая <b>\r\n\r\n\r\n  Вторая\nТретья\nЧетвёртая",
        ]);

        $html = Hook::call('feedAdvert', 'middle');

        $this->assertStringContainsString("Первая &lt;b&gt;<br />\nВторая<br />\nТретья Четвёртая", $html);
    }

    public function testSidebarCardTextLimitedWithLineBreaks(): void
    {
        $this->createCard(attributes: ['text' => "Строка\n" . str_repeat('я', 150)]);

        $html = Hook::call('sidebarFooterEnd');

        $this->assertStringContainsString("Строка<br />\n", $html);
        $this->assertStringNotContainsString(str_repeat('я', 100), $html);
    }

    public function testMyAdvertsListsCard(): void
    {
        $user = User::factory()->create();
        $card = $this->createCard($user);

        $this->actingAs($user)
            ->get('/payments/my')
            ->assertOk()
            ->assertSee('Заголовок карточки')
            ->assertSee('/payments/my/card/' . $card->id);
    }

    public function testEditForeignCardNotFound(): void
    {
        $card = $this->createCard(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get('/payments/my/card/' . $card->id)
            ->assertNotFound();
    }

    public function testLinkEditRouteRejectsCard(): void
    {
        $user = User::factory()->create();
        $card = $this->createCard($user);

        $this->actingAs($user)->get('/payments/my/edit/' . $card->id)->assertNotFound();
    }

    public function testUpdateCardTextKeepsImageNoNotify(): void
    {
        $boss = User::factory()->boss()->create();
        $user = User::factory()->create();
        $card = $this->createCard($user, ['image' => '/uploads/paid-adverts/keep.webp']);

        $this->actingAs($user)
            ->post('/payments/my/card/' . $card->id, [
                'title' => 'Новый заголовок',
                'text'  => 'Новый текст',
                'url'   => 'https://new.example.com',
            ])
            ->assertRedirect('/payments/my');

        $card->refresh();
        $this->assertSame(['Новый заголовок'], $card->names);
        $this->assertSame('Новый текст', $card->text);
        $this->assertSame('https://new.example.com', $card->url);
        $this->assertSame('/uploads/paid-adverts/keep.webp', $card->image);
        $this->assertSame(0, Message::query()->where('user_id', $boss->id)->count());
    }

    public function testUpdateCardImageNotifiesBoss(): void
    {
        $boss = User::factory()->boss()->create();
        $user = User::factory()->create();

        $old = $this->makeImageFile('old');
        $card = $this->createCard($user, ['image' => $old]);

        $this->actingAs($user)
            ->post('/payments/my/card/' . $card->id, [
                'title' => 'Заголовок карточки',
                'url'   => 'https://example.com',
                'image' => UploadedFile::fake()->image('new.png', 400, 400),
            ])
            ->assertRedirect('/payments/my');

        $card->refresh();
        $this->assertFileDoesNotExist(public_path($old));
        $this->assertNotSame($old, $card->image);
        $this->assertFileExists(public_path($card->image));
        $this->assertSame(1, Message::query()->where('user_id', $boss->id)->count());

        unlink(public_path($card->image));
    }

    /**
     * Кладёт файл картинки в каталог карточек
     */
    private function makeImageFile(string $name): string
    {
        $path = CardImageService::path() . '/' . $name . '-' . uniqid() . '.webp';
        File::ensureDirectoryExists(public_path(CardImageService::path()));
        file_put_contents(public_path($path), 'x');

        return $path;
    }

    public function testAdminIndexCardTab(): void
    {
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE]);

        $this->actingAs(User::factory()->boss()->create())
            ->get('/admin/paid-adverts?place=' . PaidAdvert::CARD_FEED_MIDDLE)
            ->assertOk()
            ->assertSee('Заголовок карточки')
            ->assertSee(__('payment::payments.cards.card_feed_middle'));
    }

    public function testAdminEditCardRemovesImage(): void
    {
        $image = $this->makeImageFile('admin');
        $card = $this->createCard(attributes: ['image' => $image]);

        $this->actingAs(User::factory()->boss()->create())
            ->post('/admin/paid-adverts/edit-card/' . $card->id, [
                'title'        => 'Заголовок карточки',
                'url'          => 'https://example.com',
                'term'         => now()->addDays(3)->format('Y-m-d H:i'),
                'remove_image' => 1,
            ])
            ->assertRedirect('/admin/paid-adverts?place=' . PaidAdvert::CARD_SIDEBAR);

        $card->refresh();
        $this->assertNull($card->image);
        $this->assertFileDoesNotExist(public_path($image));
        $this->assertTrue($card->deleted_at->isFuture());
    }

    public function testAdminIndexCardTabLinksToCreateCard(): void
    {
        $this->actingAs(User::factory()->boss()->create())
            ->get('/admin/paid-adverts?place=' . PaidAdvert::CARD_FEED_MIDDLE)
            ->assertOk()
            ->assertSee('/admin/paid-adverts/create-card?place=' . PaidAdvert::CARD_FEED_MIDDLE);
    }

    public function testAdminCreateCardPage(): void
    {
        $this->actingAs(User::factory()->boss()->create())
            ->get('/admin/paid-adverts/create-card?place=' . PaidAdvert::CARD_FEED_MIDDLE)
            ->assertOk()
            ->assertSee('name="place"', false)
            ->assertSee('name="title"', false);
    }

    public function testAdminCreateFormsHaveTabs(): void
    {
        $boss = User::factory()->boss()->create();

        foreach (['/admin/paid-adverts/create', '/admin/paid-adverts/create-card'] as $url) {
            $this->actingAs($boss)
                ->get($url)
                ->assertOk()
                ->assertSee('href="/admin/paid-adverts/create"', false)
                ->assertSee('href="/admin/paid-adverts/create-card"', false);
        }
    }

    public function testAdminCreateCardIgnoresLimit(): void
    {
        $boss = User::factory()->boss()->create();
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE]);
        $this->createCard(attributes: ['place' => PaidAdvert::CARD_FEED_MIDDLE]);

        $this->actingAs($boss)
            ->post('/admin/paid-adverts/create-card', [
                'place' => PaidAdvert::CARD_FEED_MIDDLE,
                'title' => 'Карточка от админа',
                'text'  => 'Текст',
                'url'   => 'https://example.org',
                'image' => UploadedFile::fake()->image('banner.jpg', 300, 300),
                'term'  => now()->addDays(7)->format('Y-m-d H:i'),
            ])
            ->assertRedirect('/admin/paid-adverts?place=' . PaidAdvert::CARD_FEED_MIDDLE);

        $card = PaidAdvert::query()->where('url', 'https://example.org')->first();
        $this->assertNotNull($card);
        $this->assertSame($boss->id, $card->user_id);
        $this->assertSame(['Карточка от админа'], $card->names);
        $this->assertFileExists(public_path($card->image));
        $this->assertSame(3, PaidAdvert::query()->where('place', PaidAdvert::CARD_FEED_MIDDLE)->count());
    }

    public function testAdminCreateCardRejectsLinkPlace(): void
    {
        $this->actingAs(User::factory()->boss()->create())
            ->post('/admin/paid-adverts/create-card', [
                'place' => 'top_all',
                'title' => 'Карточка от админа',
                'url'   => 'https://example.org',
                'term'  => now()->addDays(7)->format('Y-m-d H:i'),
            ])
            ->assertSessionHasErrors('place');

        $this->assertDatabaseMissing('paid_adverts', ['url' => 'https://example.org']);
    }

    public function testAdminCreateCardPastTermHasReadableError(): void
    {
        $this->actingAs(User::factory()->boss()->create())
            ->post('/admin/paid-adverts/create-card', [
                'place' => PaidAdvert::CARD_SIDEBAR,
                'title' => 'Карточка от админа',
                'url'   => 'https://example.org',
                'term'  => now()->subDay()->format('Y-m-d H:i'),
            ])
            ->assertSessionHasErrors(['term' => __('payment::payments.paid_adverts.term_invalid')]);
    }

    public function testAdminStopEndsShowAndKeepsRecord(): void
    {
        $card = $this->createCard();
        $link = $this->createCard(attributes: ['place' => PaidAdvert::TOP_ALL, 'text' => null]);

        $this->actingAs(User::factory()->boss()->create());

        foreach ([$card, $link] as $advert) {
            $this->post('/admin/paid-adverts/stop/' . $advert->id)
                ->assertRedirect('admin/paid-adverts?place=' . $advert->place)
                ->assertSessionHas('success', __('payment::payments.paid_adverts.stopped'));

            $this->assertTrue($advert->fresh()->deleted_at->lte(now()));
        }

        $this->assertNull(PaidAdvert::randomCard(PaidAdvert::CARD_SIDEBAR));

        // Снятую второй раз не снять
        $this->post('/admin/paid-adverts/stop/' . $card->id)->assertNotFound();
    }

    public function testAdminStopButtonsOnlyForActive(): void
    {
        $active = $this->createCard();
        $expired = $this->createCard(attributes: ['deleted_at' => now()->subDay()]);

        $this->actingAs(User::factory()->boss()->create());

        $this->get('/admin/paid-adverts?place=' . PaidAdvert::CARD_SIDEBAR)
            ->assertSee('/admin/paid-adverts/stop/' . $active->id, false)
            ->assertDontSee('/admin/paid-adverts/stop/' . $expired->id, false);

        $this->get('/admin/paid-adverts/edit-card/' . $active->id)
            ->assertSee('formaction="/admin/paid-adverts/stop/' . $active->id . '"', false);
        $this->get('/admin/paid-adverts/edit-card/' . $expired->id)
            ->assertDontSee('/admin/paid-adverts/stop/', false);
        $this->get('/admin/paid-adverts/create-card')
            ->assertDontSee('/admin/paid-adverts/stop/', false);

        $link = $this->createCard(attributes: ['place' => PaidAdvert::TOP_ALL, 'text' => null]);
        $this->get('/admin/paid-adverts/edit/' . $link->id)
            ->assertSee('formaction="/admin/paid-adverts/stop/' . $link->id . '"', false);
    }

    public function testAdminDeleteCardRemovesImage(): void
    {
        $image = $this->makeImageFile('delete');
        $card = $this->createCard(attributes: ['image' => $image]);

        $this->actingAs(User::factory()->boss()->create())
            ->delete('/admin/paid-adverts/delete/' . $card->id)
            ->assertRedirect();

        $this->assertModelMissing($card);
        $this->assertFileDoesNotExist(public_path($image));
    }

    public function testCleanCardImagesKeepsReferenced(): void
    {
        // Команда чистит весь каталог: в общем с сайтом она удалила бы его картинки
        $publicPath = storage_path('framework/testing/public');
        $this->app->usePublicPath($publicPath);
        $this->beforeApplicationDestroyed(static fn () => File::deleteDirectory($publicPath));

        $used = $this->makeImageFile('used');
        $orphan = $this->makeImageFile('orphan');
        $fresh = $this->makeImageFile('fresh');
        $this->createCard(attributes: ['image' => $used]);

        touch(public_path($used), now()->subDays(2)->getTimestamp());
        touch(public_path($orphan), now()->subDays(2)->getTimestamp());

        $this->app->make(Kernel::class)->registerCommand(new CleanCardImages());
        $this->artisan('payment:clean-card-images')->assertSuccessful();

        $this->assertFileExists(public_path($used));
        $this->assertFileDoesNotExist(public_path($orphan));
        $this->assertFileExists(public_path($fresh));
    }

    public function testCardRenewFormOnOwnFullPlace(): void
    {
        $user = User::factory()->create();
        $card = $this->createCard($user, ['image' => '/uploads/paid-adverts/renew.webp']);
        $this->createCard();

        // Место занято целиком, но продлеваемая карточка его не занимает
        $this->actingAs($user)
            ->get('/payments/my/card/renew/' . $card->id)
            ->assertOk()
            ->assertSee('name="renew_id" value="' . $card->id . '"', false)
            ->assertSee('/uploads/paid-adverts/renew.webp')
            ->assertSee('Заголовок карточки')
            ->assertDontSee(Str::before(__('payment::payments.cards.place_full'), ':date'));
    }

    public function testMyAdvertsShowsExpiredCardWithImage(): void
    {
        $user = User::factory()->create();
        $card = $this->createCard($user, ['image' => '/uploads/paid-adverts/expired.webp', 'deleted_at' => now()->subDay()]);

        $this->actingAs($user)
            ->get('/payments/my')
            ->assertOk()
            ->assertSee('/uploads/paid-adverts/expired.webp')
            ->assertSee($card->renewUrl());
    }

    public function testCardRenewForeignNotFound(): void
    {
        $user = User::factory()->create();
        $foreign = $this->createCard(User::factory()->create());

        $this->actingAs($user)->get('/payments/my/card/renew/' . $foreign->id)->assertNotFound();

        $this->actingAs($user)
            ->post('/payments/card/calculate', [
                'place'    => PaidAdvert::CARD_SIDEBAR,
                'title'    => 'Хостинг для сайтов',
                'url'      => 'https://example.com',
                'term'     => 5,
                'email'    => 'test@example.com',
                'renew_id' => $foreign->id,
            ])
            ->assertNotFound();
    }

    public function testCardCalculateRenewOnOwnFullPlace(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();
        $card = $this->createCard($user, ['image' => '/uploads/paid-adverts/renew.webp']);
        $this->createCard();

        $this->actingAs($user)
            ->post('/payments/card/calculate', [
                'place'    => PaidAdvert::CARD_SIDEBAR,
                'title'    => 'Хостинг для сайтов',
                'url'      => 'https://example.com',
                'term'     => 5,
                'email'    => 'test@example.com',
                'renew_id' => $card->id,
            ])
            ->assertOk()
            ->assertSee('100')
            ->assertSee('/uploads/paid-adverts/renew.webp')
            ->assertSee(dateFixed(now()->addDays(15)));
    }

    public function testCardPayRenewOnOwnFullPlace(): void
    {
        $user = User::factory()->create();
        $card = $this->createCard($user);
        $this->createCard();

        $data = $this->cardData([
            'place'    => PaidAdvert::CARD_SIDEBAR,
            'prices'   => ['total' => 100, 'place' => 100],
            'renew_id' => $card->id,
        ]);

        $this->actingAs($user);
        $service = app(PaymentService::class);

        $this->assertNull($service->validateCardOrder($data));
        $this->assertNotNull($service->validateCardOrder([...$data, 'renew_id' => null]));
    }

    public function testFulfillRenewCardKeepsImageAndExtendsFromEnd(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();
        $image = $this->makeImageFile('keep');
        $card = $this->createCard($user, ['place' => PaidAdvert::CARD_FEED_MIDDLE, 'image' => $image]);

        $this->fulfillCardRenew($user, $card);

        $card->refresh();
        $this->assertDatabaseCount('paid_adverts', 1);
        $this->assertSame(['Хостинг для сайтов'], $card->names);
        $this->assertSame($image, $card->image);
        $this->assertFileExists(public_path($image));
        $this->assertSame(now()->addDays(15)->getTimestamp(), $card->deleted_at->getTimestamp());
    }

    public function testFulfillRenewCardReplacesImageAndNotifiesBoss(): void
    {
        $boss = User::factory()->boss()->create(['language' => 'ru']);
        $user = User::factory()->create();
        $old = $this->makeImageFile('old');
        $new = $this->makeImageFile('new');
        $card = $this->createCard($user, ['image' => $old]);

        $this->fulfillCardRenew($user, $card, ['image' => $new]);

        $this->assertSame($new, $card->fresh()->image);
        $this->assertFileDoesNotExist(public_path($old));

        $message = Message::query()->where('user_id', $boss->id)->sole();
        $this->assertStringContainsString('/admin/paid-adverts/edit-card/' . $card->id, $message->text);
    }

    public function testFulfillRenewCardRemovesImage(): void
    {
        $user = User::factory()->create();
        $old = $this->makeImageFile('old');
        $card = $this->createCard($user, ['image' => $old]);

        $this->fulfillCardRenew($user, $card, ['remove_image' => true]);

        $this->assertNull($card->fresh()->image);
        $this->assertFileDoesNotExist(public_path($old));
    }

    public function testFulfillRenewCardOnPricierPlaceRecalculatesRemainder(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();
        $card = $this->createCard($user, ['deleted_at' => now()->addDays(10)]);

        // Сайдбар 20 в сутки, середина ленты 30: 10 оставшихся дней становятся 10 × 20 / 30
        $this->fulfillCardRenew($user, $card);

        $card->refresh();
        $this->assertSame(PaidAdvert::CARD_FEED_MIDDLE, $card->place);
        $this->assertSame(
            now()->addDays(5)->addSeconds(intdiv(10 * 86400 * 20, 30))->getTimestamp(),
            $card->deleted_at->getTimestamp(),
        );
    }

    public function testFulfillRenewCardOnFreePlaceKeepsRemainder(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();
        // Место без цены (добавлена админом бесплатно): пересчитать остаток не через что
        $card = $this->createCard($user, ['place' => PaidAdvert::CARD_FEED_END, 'deleted_at' => now()->addDays(10)]);

        $this->fulfillCardRenew($user, $card);

        $this->assertSame(now()->addDays(15)->getTimestamp(), $card->fresh()->deleted_at->getTimestamp());
    }

    /**
     * Выполняет оплаченный заказ продления карточки
     */
    private function fulfillCardRenew(User $user, PaidAdvert $card, array $data = []): void
    {
        $order = Order::factory()->create([
            'user_id'     => $user->id,
            'type'        => Order::TYPE_CARD,
            'amount'      => 150,
            'currency'    => 'RUB',
            'token'       => 'test-token-renew-123456',
            'payment_id'  => 'pay-renew',
            'status'      => YooKassaService::PENDING,
            'email'       => 'test@example.com',
            'description' => 'Рекламная карточка',
            'data'        => $this->cardData([...$data, 'renew_id' => $card->id]),
        ]);

        app(PaymentService::class)->fulfill($order);
    }

    public function testSettingsPageShowsCardFields(): void
    {
        $boss = User::factory()->boss()->create();

        $this->actingAs($boss)
            ->get(route('payment.settings'))
            ->assertOk()
            ->assertSee('sets[payment_price_card_sidebar]', false)
            ->assertSee('sets[payment_price_card_feed_start]', false)
            ->assertSee('sets[payment_price_card_feed_middle]', false)
            ->assertSee('sets[payment_price_card_feed_end]', false)
            ->assertSee('sets[payment_card_limit]', false);
    }
}
