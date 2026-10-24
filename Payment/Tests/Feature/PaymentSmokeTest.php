<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\Services\YooKassaService;
use Tests\ModuleTestCase;

class PaymentSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Payment';

    protected function setUp(): void
    {
        parent::setUp();

        // Незафейканный запрос — ошибка теста, а не молчаливый поход в реальную сеть
        Http::preventStrayRequests();

        config(['payment' => require base_path('modules/Payment/config.php')]);

        $this->seedSettings();
    }

    public function testAdminListsSeveralOrders(): void
    {
        // Покупатель у списка заказов грузился по одному: ленивую загрузку ловит только список из нескольких
        $buyer = User::factory()->create();
        Order::factory()->count(2)->create(['user_id' => $buyer->id]);

        $this->actingAs(User::factory()->boss()->create())
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee($buyer->login);
    }

    /**
     * Сеет настройки модуля (SettingSeeder в тестах очищает таблицу settings)
     */
    private function seedSettings(): void
    {
        $settings = [
            'payment_price_top'   => 35,
            'payment_price_color' => 3,
            'payment_price_bold'  => 3,
            'payment_price_name'  => 1,

            'payment_balance_enabled' => 1,
            'payment_balance_rate'    => 10,
            'payment_balance_min'     => 10,
        ];

        foreach ($settings as $name => $value) {
            Setting::query()->updateOrInsert(['name' => $name], ['value' => $value]);
        }

        clearCache('settings');
    }

    public function testAdvertIndex(): void
    {
        $this->get('/payments/advert')->assertOk();
    }

    public function testCalculate(): void
    {
        $response = $this->post('/payments/calculate', [
            'place'   => PaidAdvert::TOP,
            'url'     => 'https://example.com',
            'names'   => ['Test advert name'],
            'color'   => '#ff0000',
            'bold'    => 1,
            'term'    => 10,
            'comment' => 'Test comment',
            'email'   => 'test@example.com',
        ]);

        // top 35*10 + color 3*10 + bold 3*10 = 410
        $response->assertOk();
        $response->assertSee('410');
    }

    public function testCalculateInvalid(): void
    {
        $this->post('/payments/calculate', [
            'place' => 'unknown',
            'url'   => 'not-a-url',
        ])->assertRedirect();
    }

    public function testPay(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'id'           => 'pay-123',
                'status'       => YooKassaService::PENDING,
                'confirmation' => ['confirmation_url' => 'https://yookassa.test/confirm'],
            ]),
        ]);

        $data = Crypt::encrypt($this->advertData());

        $this->post('/payments/pay', ['data' => $data])
            ->assertRedirect('https://yookassa.test/confirm');

        $this->assertDatabaseHas('orders', [
            'payment_id' => 'pay-123',
            'status'     => YooKassaService::PENDING,
            'amount'     => 410,
            'currency'   => config('payment.yookassa_currency'),
        ]);
    }

    public function testStatus(): void
    {
        $order = $this->createOrder();

        $this->get('/payments/status?token=' . $order->token)->assertOk();
    }

    public function testStatusNotFound(): void
    {
        $this->get('/payments/status?token=unknown')->assertNotFound();
    }

    public function testWebhookSucceeded(): void
    {
        $order = $this->createOrder();

        Http::fake([
            'api.yookassa.ru/v3/payments/pay-123' => Http::response([
                'id'     => 'pay-123',
                'status' => YooKassaService::SUCCEEDED,
            ]),
        ]);

        $this->post('/payments/webhook', [
            'event'  => 'payment.succeeded',
            'object' => ['id' => 'pay-123'],
        ])->assertStatus(200);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => YooKassaService::SUCCEEDED]);
        $this->assertDatabaseCount('paid_adverts', 1);
    }

    public function testWebhookCanceled(): void
    {
        $order = $this->createOrder();

        Http::fake([
            'api.yookassa.ru/v3/payments/pay-123' => Http::response([
                'id'     => 'pay-123',
                'status' => YooKassaService::CANCELED,
            ]),
        ]);

        $this->post('/payments/webhook', [
            'event'  => 'payment.canceled',
            'object' => ['id' => 'pay-123'],
        ])->assertStatus(200);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => YooKassaService::CANCELED]);
        $this->assertDatabaseCount('paid_adverts', 0);
    }

    public function testWebhookInvalidData(): void
    {
        $this->post('/payments/webhook')->assertStatus(400);
    }

    public function testOrdersRequireBoss(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/orders')->assertForbidden();
    }

    public function testOrdersAccessibleByBoss(): void
    {
        $boss = User::factory()->boss()->create();

        $this->createOrder();

        $this->actingAs($boss)->get('/admin/orders')->assertOk();
    }

    public function testSettingsPage(): void
    {
        $boss = User::factory()->boss()->create();

        $this->actingAs($boss)->get(route('payment.settings'))->assertOk();
    }

    public function testSettingsUpdate(): void
    {
        $boss = User::factory()->boss()->create();

        $this->actingAs($boss)
            ->post(route('payment.settings.update'), [
                'sets' => ['payment_price_top' => '40'],
            ])
            ->assertRedirect(route('payment.settings'));

        $this->assertDatabaseHas('settings', ['name' => 'payment_price_top', 'value' => '40']);
    }

    /**
     * Данные объявления (как после calculate)
     */
    private function advertData(): array
    {
        return [
            'type'        => Order::TYPE_ADVERT,
            'place'       => PaidAdvert::TOP,
            'url'         => 'https://example.com',
            'names'       => ['Test advert name'],
            'color'       => '#ff0000',
            'bold'        => 1,
            'term'        => 10,
            'comment'     => 'Test comment',
            'email'       => 'test@example.com',
            'description' => 'Оплата рекламных услуг',
            'prices'      => [
                'total' => 410,
                'place' => 350,
                'color' => 30,
                'bold'  => 30,
                'names' => 0,
            ],
        ];
    }

    public function testMyAdvertsGuestForbidden(): void
    {
        $this->get('/payments/my')->assertForbidden();
    }

    public function testMyAdverts(): void
    {
        $user = User::factory()->create();
        $this->createAdvert($user);

        $this->actingAs($user)
            ->get('/payments/my')
            ->assertOk()
            ->assertSee('Test advert name');
    }

    public function testEditForeignAdvertNotFound(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $advert = $this->createAdvert($owner);

        $this->actingAs($user)->get('/payments/my/edit/' . $advert->id)->assertNotFound();
    }

    public function testUpdateAdvert(): void
    {
        $user = User::factory()->create();
        $advert = $this->createAdvert($user, ['color' => '#ff0000', 'bold' => true]);

        $this->actingAs($user)->post('/payments/my/edit/' . $advert->id, [
            'url'   => 'https://new-site.com',
            'names' => ['New advert name'],
            'color' => '#00ff00',
            'bold'  => 0,
        ])->assertRedirect('/payments/my');

        $advert->refresh();
        $this->assertSame('https://new-site.com', $advert->url);
        $this->assertSame(['New advert name'], $advert->names);
        $this->assertSame('#00ff00', $advert->color);
        $this->assertTrue($advert->bold);
    }

    public function testUpdateCannotDisablePaidOptions(): void
    {
        $user = User::factory()->create();
        $advert = $this->createAdvert($user, ['color' => '#ff0000', 'bold' => true]);

        $this->actingAs($user)->post('/payments/my/edit/' . $advert->id, [
            'url'   => 'https://example.com',
            'names' => ['Test advert name'],
            'color' => '',
        ])->assertSessionHasErrors('color');

        $advert->refresh();
        $this->assertSame('#ff0000', $advert->color);
        $this->assertTrue($advert->bold);
    }

    public function testUpdateCannotEnableUnpaidOptions(): void
    {
        $user = User::factory()->create();
        $advert = $this->createAdvert($user);

        $this->actingAs($user)->post('/payments/my/edit/' . $advert->id, [
            'url'   => 'https://example.com',
            'names' => ['Test advert name'],
            'color' => '#00ff00',
            'bold'  => 1,
        ])->assertRedirect('/payments/my');

        $advert->refresh();
        $this->assertEmpty($advert->color);
        $this->assertFalse($advert->bold);
    }

    public function testUpdateCannotExceedPaidNames(): void
    {
        $user = User::factory()->create();
        $advert = $this->createAdvert($user);

        $this->actingAs($user)->post('/payments/my/edit/' . $advert->id, [
            'url'   => 'https://example.com',
            'names' => ['First advert name', 'Second advert name'],
        ])->assertSessionHasErrors('names');
    }

    /**
     * Создает активную рекламу пользователя
     */
    private function createAdvert(User $user, array $attributes = []): PaidAdvert
    {
        return PaidAdvert::factory()->create(array_merge([
            'place'      => PaidAdvert::TOP,
            'url'        => 'https://example.com',
            'names'      => ['Test advert name'],
            'color'      => null,
            'bold'       => false,
            'user_id'    => $user->id,
            'created_at' => now()->timestamp,
            'deleted_at' => now()->addDay()->timestamp,
        ], $attributes));
    }

    public function testAdminCreateAdvert(): void
    {
        $boss = User::factory()->boss()->create();

        $this->actingAs($boss)->post('/admin/paid-adverts/create', [
            'place'   => PaidAdvert::TOP_ALL,
            'url'     => 'https://admin-site.com',
            'names'   => ['Admin advert', '', 'Admin advert', 'Second name'],
            'color'   => '#00ff00',
            'bold'    => 1,
            'term'    => now()->addDays(7)->format('Y-m-d H:i'),
            'comment' => 'Без оплаты',
        ])->assertRedirect('admin/paid-adverts?place=' . PaidAdvert::TOP_ALL);

        $advert = PaidAdvert::query()->where('url', 'https://admin-site.com')->firstOrFail();
        $this->assertSame($boss->id, $advert->user_id);
        $this->assertSame(['Admin advert', 'Second name'], $advert->names);
        $this->assertSame('#00ff00', $advert->color);
        $this->assertTrue($advert->bold);
        $this->assertTrue($advert->deleted_at->gt(now()));
    }

    public function testAdminCreateAdvertReadableErrors(): void
    {
        $this->actingAs(User::factory()->boss()->create())
            ->post('/admin/paid-adverts/create', [
                'place' => PaidAdvert::TOP_ALL,
                'url'   => 'https://admin-site.com',
                'names' => ['', 'abc'],
                'term'  => now()->subDay()->format('Y-m-d H:i'),
            ])
            ->assertRedirect('/admin/paid-adverts/create?place=' . PaidAdvert::TOP_ALL)
            ->assertSessionHasErrors([
                'term'  => __('payment::payments.paid_adverts.term_invalid'),
                'names' => __('validation.min.string', ['attribute' => __('payment::payments.paid_adverts.name'), 'min' => 5]),
            ]);

        $this->post('/admin/paid-adverts/create', ['place' => PaidAdvert::TOP_ALL, 'names' => ['']])
            ->assertSessionHasErrors(['names' => __('payment::payments.paid_adverts.names_count')]);

        $this->assertDatabaseMissing('paid_adverts', ['url' => 'https://admin-site.com']);
    }

    public function testAdminUpdateAdvert(): void
    {
        $advert = $this->createAdvert(User::factory()->create(), ['bold' => true]);

        $this->actingAs(User::factory()->boss()->create())
            ->post('/admin/paid-adverts/edit/' . $advert->id, [
                'place' => PaidAdvert::BOTTOM_ALL,
                'url'   => 'https://moved-site.com',
                'names' => ['Moved advert'],
                'term'  => now()->addDays(3)->format('Y-m-d H:i'),
            ])
            ->assertRedirect('admin/paid-adverts?place=' . PaidAdvert::BOTTOM_ALL);

        $advert->refresh();
        $this->assertSame(PaidAdvert::BOTTOM_ALL, $advert->place);
        $this->assertSame('https://moved-site.com', $advert->url);
        $this->assertFalse($advert->bold);
    }

    public function testAdminEditAdvertIgnoresCards(): void
    {
        $card = $this->createAdvert(User::factory()->create(), ['place' => PaidAdvert::CARD_SIDEBAR]);

        $this->actingAs(User::factory()->boss()->create());

        $this->get('/admin/paid-adverts/edit/' . $card->id)->assertNotFound();
        $this->post('/admin/paid-adverts/edit/' . $card->id, [
            'place' => PaidAdvert::TOP_ALL,
            'url'   => 'https://example.org',
            'names' => ['Card to link'],
            'term'  => now()->addDay()->format('Y-m-d H:i'),
        ])->assertNotFound();

        $this->assertSame(PaidAdvert::CARD_SIDEBAR, $card->fresh()->place);
    }

    public function testStatusShowsContinuePaymentForFreshOrder(): void
    {
        $order = $this->createOrder(['payment_url' => 'https://yoomoney.ru/checkout/test-pay']);

        $this->get('/payments/status?token=' . $order->token)
            ->assertOk()
            ->assertSee('https://yoomoney.ru/checkout/test-pay');
    }

    public function testStatusHidesContinuePaymentForExpiredOrder(): void
    {
        $order = $this->createOrder([
            'payment_url' => 'https://yoomoney.ru/checkout/test-pay',
            'created_at'  => now()->subHour(),
        ]);

        $this->get('/payments/status?token=' . $order->token)
            ->assertOk()
            ->assertDontSee('https://yoomoney.ru/checkout/test-pay');
    }

    public function testMyAdvertsShowsPendingOrder(): void
    {
        $user = User::factory()->create();
        $this->createOrder([
            'user_id'     => $user->id,
            'payment_url' => 'https://yoomoney.ru/checkout/test-pay',
        ]);

        $this->actingAs($user)
            ->get('/payments/my')
            ->assertOk()
            ->assertSee('https://yoomoney.ru/checkout/test-pay');
    }

    public function testBalanceGuestForbidden(): void
    {
        $this->get('/payments/balance')->assertForbidden();
    }

    public function testBalanceDisabled(): void
    {
        Setting::query()->where('name', 'payment_balance_enabled')->update(['value' => 0]);
        clearCache('settings');

        $user = User::factory()->create();

        $this->actingAs($user)->get('/payments/balance')->assertNotFound();
        $this->actingAs($user)->post('/payments/balance', ['amount' => 100])->assertNotFound();
    }

    public function testBalanceIndex(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/payments/balance')
            ->assertOk()
            ->assertSee('value="100"', false);
    }

    public function testBalancePay(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'id'           => 'pay-456',
                'status'       => YooKassaService::PENDING,
                'confirmation' => ['confirmation_url' => 'https://yookassa.test/confirm'],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/payments/balance', ['amount' => 150, 'email' => 'test@example.com'])
            ->assertRedirect('https://yookassa.test/confirm');

        $order = Order::query()->where('payment_id', 'pay-456')->first();

        $this->assertNotNull($order);
        $this->assertSame(Order::TYPE_BALANCE, $order->type);
        $this->assertSame($user->id, $order->user_id);
        // 150 рублей по курсу 10
        $this->assertSame(150, $order->amount);
        $this->assertSame(1500, $order->data['coins']);
    }

    public function testBalancePayFailedMarksOrderError(): void
    {
        // Неверные ключи магазина: ЮКасса отвечает 401
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'type'        => 'error',
                'description' => 'Authentication by given credentials failed',
            ], 401),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/payments/balance', ['amount' => 100, 'email' => 'test@example.com'])
            ->assertRedirect('/payments/balance')
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'type'    => Order::TYPE_BALANCE,
            'status'  => YooKassaService::ERROR,
        ]);
    }

    public function testBalancePayInvalidAmount(): void
    {
        $user = User::factory()->create();

        // Дробные рубли
        $this->actingAs($user)
            ->post('/payments/balance', ['amount' => 100.5, 'email' => 'test@example.com'])
            ->assertSessionHasErrors('amount');

        // Меньше минимума
        $this->actingAs($user)
            ->post('/payments/balance', ['amount' => 5, 'email' => 'test@example.com'])
            ->assertSessionHasErrors('amount');

        // Больше предела одного платежа
        $this->actingAs($user)
            ->post('/payments/balance', ['amount' => 100001, 'email' => 'test@example.com'])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('orders', 0);
    }

    public function testWebhookBalanceCreditsOnce(): void
    {
        $user = User::factory()->create(['money' => 100, 'language' => 'ru']);

        $this->createOrder([
            'user_id' => $user->id,
            'type'    => Order::TYPE_BALANCE,
            'amount'  => 1000,
            'data'    => [
                'type'   => Order::TYPE_BALANCE,
                'coins'  => 10000,
                'email'  => 'test@example.com',
                'prices' => ['total' => 1000],
            ],
        ]);

        Http::fake([
            'api.yookassa.ru/v3/payments/pay-123' => Http::response([
                'id'     => 'pay-123',
                'status' => YooKassaService::SUCCEEDED,
            ]),
        ]);

        $webhook = [
            'event'  => 'payment.succeeded',
            'object' => ['id' => 'pay-123'],
        ];

        // Повторное уведомление ЮКассы не начисляет второй раз.
        // Язык запроса чужой: уведомление всё равно на языке получателя
        $headers = ['Accept-Language' => 'en-US,en;q=0.9'];
        $this->post('/payments/webhook', $webhook, $headers)->assertStatus(200);
        $this->post('/payments/webhook', $webhook, $headers)->assertStatus(200);

        $this->assertSame(10100, $user->fresh()->money);
        $this->assertDatabaseCount('paid_adverts', 0);

        $text = Message::query()->where('user_id', $user->id)->where('author_id', 0)->value('text');
        $this->assertStringContainsString('Баланс пополнен', (string) $text);
    }

    public function testProfileShowsBalanceLink(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/users/' . $user->login)
            ->assertOk()
            ->assertSee('/payments/balance');
    }

    public function testMyAdvertsShowsRecentlyExpiredForRenew(): void
    {
        $user = User::factory()->create();
        $expired = $this->createAdvert($user, ['names' => ['Expired advert name'], 'deleted_at' => now()->subDays(5)]);
        $this->createAdvert($user, ['names' => ['Ancient advert name'], 'deleted_at' => now()->subDays(100)]);

        $this->actingAs($user)
            ->get('/payments/my')
            ->assertOk()
            ->assertSee(__('payment::payments.paid_adverts.expired_title'))
            ->assertSee('Expired advert name')
            ->assertSee('/payments/my/renew/' . $expired->id)
            ->assertDontSee('Ancient advert name');
    }

    public function testRenewFormPrefilled(): void
    {
        $user = User::factory()->create();
        $advert = $this->createAdvert($user, [
            'url'        => 'https://renew-site.com',
            'names'      => ['First advert name', 'Second advert name'],
            'comment'    => 'Оплата рекламных услуг #15',
            'deleted_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get('/payments/my/renew/' . $advert->id)
            ->assertOk()
            ->assertSee('https://renew-site.com')
            ->assertSee('Second advert name')
            ->assertSee('name="renew_id" value="' . $advert->id . '"', false)
            ->assertDontSee('Оплата рекламных услуг #15');
    }

    public function testRenewForeignOrCardNotFound(): void
    {
        $user = User::factory()->create();
        $foreign = $this->createAdvert(User::factory()->create());
        $card = $this->createAdvert($user, ['place' => PaidAdvert::CARD_SIDEBAR]);

        $this->actingAs($user)->get('/payments/my/renew/' . $foreign->id)->assertNotFound();
        $this->actingAs($user)->get('/payments/my/renew/' . $card->id)->assertNotFound();
    }

    public function testCalculateRenewForeignNotFound(): void
    {
        $foreign = $this->createAdvert(User::factory()->create());
        $guestAdvert = PaidAdvert::factory()->create(['user_id' => null, 'deleted_at' => now()->addDay()]);

        $request = [...$this->advertData(), 'names' => ['Test advert name']];

        $this->actingAs(User::factory()->create())
            ->post('/payments/calculate', [...$request, 'renew_id' => $foreign->id])
            ->assertNotFound();

        auth()->logout();

        // У гостя своей рекламы нет: купленная гостем не становится «своей»
        $this->post('/payments/calculate', [...$request, 'renew_id' => $guestAdvert->id])
            ->assertNotFound();
    }

    public function testCalculateRenewShowsNewEnd(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();
        $advert = $this->createAdvert($user, ['color' => '#ff0000', 'bold' => true, 'deleted_at' => now()->addDays(5)]);

        $this->actingAs($user)
            ->post('/payments/calculate', [...$this->advertData(), 'renew_id' => $advert->id])
            ->assertOk()
            ->assertSee('410')
            ->assertSee(dateFixed(now()->addDays(15)));
    }

    public function testFulfillRenewsActiveAdvertFromEnd(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();
        $advert = $this->createAdvert($user, ['color' => '#0000ff', 'bold' => true, 'deleted_at' => now()->addDays(5)]);

        $this->fulfillRenew($user, $advert, ['names' => ['Renewed advert name']]);

        $advert->refresh();
        $this->assertDatabaseCount('paid_adverts', 1);
        $this->assertSame(['Renewed advert name'], $advert->names);
        $this->assertSame('#ff0000', $advert->color);
        $this->assertSame(now()->addDays(15)->getTimestamp(), $advert->deleted_at->getTimestamp());
    }

    public function testFulfillRenewWithAddedOptionsShortensRemainder(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();
        $advert = $this->createAdvert($user, ['deleted_at' => now()->addDays(5)]);

        // Было 35 в сутки, с цветом и жирным 41: 5 оставшихся дней становятся 5 × 35 / 41
        $this->fulfillRenew($user, $advert);

        $advert->refresh();
        $this->assertTrue($advert->bold);
        $this->assertSame(
            now()->addDays(10)->addSeconds(intdiv(5 * 86400 * 35, 41))->getTimestamp(),
            $advert->deleted_at->getTimestamp(),
        );
    }

    public function testFulfillRenewWithRemovedOptionsExtendsRemainder(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();
        $advert = $this->createAdvert($user, ['color' => '#ff0000', 'bold' => true, 'deleted_at' => now()->addDays(5)]);

        // Было 41 в сутки, без цвета и жирного 35: оплаченный остаток не пропадает
        $this->fulfillRenew($user, $advert, ['color' => null, 'bold' => 0]);

        $advert->refresh();
        $this->assertFalse($advert->bold);
        $this->assertSame(
            now()->addDays(10)->addSeconds(intdiv(5 * 86400 * 41, 35))->getTimestamp(),
            $advert->deleted_at->getTimestamp(),
        );
    }

    public function testFulfillRenewsExpiredAdvertFromNow(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();
        $advert = $this->createAdvert($user, ['color' => '#ff0000', 'bold' => true, 'deleted_at' => now()->subDays(3)]);

        $this->fulfillRenew($user, $advert);

        $this->assertDatabaseCount('paid_adverts', 1);
        $this->assertSame(now()->addDays(10)->getTimestamp(), $advert->fresh()->deleted_at->getTimestamp());
    }

    public function testFulfillRenewOfMissingAdvertCreatesNew(): void
    {
        $user = User::factory()->create();
        $foreign = $this->createAdvert(User::factory()->create(), ['names' => ['Foreign advert name']]);

        $this->fulfillRenew($user, $foreign);

        $this->assertSame(['Foreign advert name'], $foreign->fresh()->names);
        $this->assertDatabaseCount('paid_adverts', 2);
        $this->assertSame(1, PaidAdvert::query()->where('user_id', $user->id)->count());
    }

    public function testFulfillLongCommentFitsColumn(): void
    {
        $order = $this->createOrder(['data' => [...$this->advertData(), 'comment' => str_repeat('я', 255)]]);

        app(PaymentService::class)->fulfill($order);

        $this->assertSame(191, mb_strlen(PaidAdvert::query()->sole()->comment));
    }

    /**
     * Выполняет оплаченный заказ продления рекламы
     */
    private function fulfillRenew(User $user, PaidAdvert $advert, array $data = []): void
    {
        $order = $this->createOrder([
            'user_id' => $user->id,
            'data'    => [...$this->advertData(), ...$data, 'renew_id' => $advert->id],
        ]);

        app(PaymentService::class)->fulfill($order);
    }

    /**
     * Создает заказ в статусе pending
     */
    private function createOrder(array $attributes = []): Order
    {
        $data = $this->advertData();

        return Order::factory()->create(array_merge([
            'user_id'     => null,
            'type'        => Order::TYPE_ADVERT,
            'amount'      => $data['prices']['total'],
            'currency'    => 'RUB',
            'token'       => 'test-token-1234567890',
            'payment_id'  => 'pay-123',
            'status'      => YooKassaService::PENDING,
            'email'       => $data['email'],
            'description' => $data['description'],
            'data'        => $data,
        ], $attributes));
    }
}
