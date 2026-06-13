<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;
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
            'site'    => 'https://example.com',
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
            'site'  => 'not-a-url',
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
            'site'        => 'https://example.com',
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
            'site'  => 'https://new-site.com',
            'names' => ['New advert name'],
            'color' => '#00ff00',
            'bold'  => 0,
        ])->assertRedirect('/payments/my');

        $advert->refresh();
        $this->assertSame('https://new-site.com', $advert->site);
        $this->assertSame(['New advert name'], $advert->names);
        $this->assertSame('#00ff00', $advert->color);
        $this->assertFalse($advert->bold);
    }

    public function testUpdateCannotEnableUnpaidOptions(): void
    {
        $user = User::factory()->create();
        $advert = $this->createAdvert($user);

        $this->actingAs($user)->post('/payments/my/edit/' . $advert->id, [
            'site'  => 'https://example.com',
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
            'site'  => 'https://example.com',
            'names' => ['First advert name', 'Second advert name'],
        ])->assertSessionHasErrors('names');
    }

    /**
     * Создает активную рекламу пользователя
     */
    private function createAdvert(User $user, array $attributes = []): PaidAdvert
    {
        return PaidAdvert::query()->create(array_merge([
            'place'      => PaidAdvert::TOP,
            'site'       => 'https://example.com',
            'names'      => ['Test advert name'],
            'color'      => null,
            'bold'       => false,
            'user_id'    => $user->id,
            'created_at' => SITETIME,
            'deleted_at' => SITETIME + 86400,
        ], $attributes));
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

    /**
     * Создает заказ в статусе pending
     */
    private function createOrder(array $attributes = []): Order
    {
        $data = $this->advertData();

        return Order::query()->create(array_merge([
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
