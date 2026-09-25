<?php

namespace Modules\Notifier\Tests\Feature;

use App\Models\Dialogue;
use App\Models\Message;
use App\Models\Online;
use App\Models\Setting;
use App\Models\User;
use App\Support\Hook;
use Modules\Notifier\Support\Notifier;
use Tests\ModuleTestCase;

class NotifierTest extends ModuleTestCase
{
    protected string $moduleName = 'Notifier';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('notifier_active', 1);
        $this->overrideSetting('notifier_interval', 60);
        $this->overrideSetting('notifier_sound', 'ding.mp3');
        $this->overrideSetting('notifier_volume', 70);
        $this->overrideSetting('notifier_title', 1);
        $this->overrideSetting('notifier_desktop', 0);

        $this->user = User::factory()->create(['level' => User::USER]);
    }

    public function testCheckReturnsUnreadCount(): void
    {
        $this->createDialogues(3);
        $this->createDialogues(2, true);

        $response = $this->actingAs($this->user)->getJson('/notifier/check');

        $response->assertOk()->assertJson(['auth' => true, 'count' => 3]);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function testCheckDoesNotRaiseUserOnline(): void
    {
        $visited = now()->subDay()->startOfSecond();
        $this->user->update(['updated_at' => $visited]);

        $this->actingAs($this->user)
            ->getJson('/notifier/check')
            ->assertOk();

        $this->assertSame(0, Online::query()->count());
        $this->assertTrue($this->user->fresh()->updated_at->eq($visited));
    }

    public function testCheckForGuest(): void
    {
        $this->getJson('/notifier/check')
            ->assertOk()
            ->assertJson(['auth' => false, 'count' => 0]);
    }

    public function testCheckWhenModuleDisabled(): void
    {
        $this->overrideSetting('notifier_active', 0);
        $this->createDialogues(2);

        $this->actingAs($this->user)
            ->getJson('/notifier/check')
            ->assertOk()
            ->assertJson(['auth' => false, 'count' => 0]);
    }

    public function testSeedMatchesCheckResponse(): void
    {
        // Затравка страницы и ответ опроса обязаны совпадать: расхождение
        // клиент читает как новое письмо и отыгрывает звук на каждой загрузке
        $this->createDialogues(3);
        $this->createDialogues(2, true);

        $this->actingAs($this->user->fresh());

        $seed = Notifier::config($this->user->fresh())['count'];

        $response = $this->getJson('/notifier/check');
        $response->assertOk();

        $this->assertSame(3, $seed);
        $this->assertSame($seed, $response->json('count'));
    }

    public function testSeedIsRenderedIntoPage(): void
    {
        $this->createDialogues(2);

        $this->actingAs($this->user->fresh());

        $this->assertStringContainsString('"count":2', Hook::call('footer'));
    }

    public function testFooterHookRendersScript(): void
    {
        $this->actingAs($this->user);

        $footer = Hook::call('footer');

        $this->assertStringContainsString('js-notifier-config', $footer);
        $this->assertStringContainsString('notifiers/js/notifier.js', $footer);
        $this->assertStringContainsString('"url":"/notifier/check"', $footer);
    }

    public function testFooterHookIsSilentForGuestAndWhenDisabled(): void
    {
        $this->assertStringNotContainsString('js-notifier-config', Hook::call('footer'));

        $this->overrideSetting('notifier_active', 0);
        $this->actingAs($this->user);

        $this->assertStringNotContainsString('js-notifier-config', Hook::call('footer'));
    }

    public function testSoundsAreListed(): void
    {
        $sounds = Notifier::sounds();

        $this->assertArrayHasKey('ding.mp3', $sounds);
        $this->assertNotNull(Notifier::soundUrl('ding.mp3'));
        $this->assertNull(Notifier::soundUrl('missing.mp3'));
    }

    public function testIntervalIsClamped(): void
    {
        $this->overrideSetting('notifier_interval', 1);
        $this->assertSame(Notifier::MIN_INTERVAL, Notifier::interval());

        $this->overrideSetting('notifier_interval', 99999);
        $this->assertSame(Notifier::MAX_INTERVAL, Notifier::interval());

        $this->overrideSetting('notifier_interval', 0);
        $this->assertSame(Notifier::DEFAULT_INTERVAL, Notifier::interval());
    }

    public function testAdminSavesSettingsWithoutSound(): void
    {
        $admin = User::factory()->create(['level' => User::BOSS]);

        // «Без звука» — пустое значение: приходит null, а колонка value NOT NULL
        $this->actingAs($admin)
            ->post('/admin/notifier-settings', [
                'sets' => ['notifier_sound' => ''],
            ])
            ->assertRedirect(route('notifier.settings'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['name' => 'notifier_sound', 'value' => '']);
    }

    public function testAdminSavesSettings(): void
    {
        $admin = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($admin)
            ->post('/admin/notifier-settings', [
                'sets' => [
                    'notifier_active'   => 1,
                    'notifier_interval' => 30,
                    'notifier_sound'    => 'chime.wav',
                    'notifier_volume'   => 40,
                    'notifier_title'    => 0,
                    'notifier_desktop'  => 1,
                ],
            ])
            ->assertRedirect(route('notifier.settings'))
            ->assertSessionHas('success');

        Setting::flush();

        $this->assertSame('30', (string) setting('notifier_interval'));
        $this->assertSame('chime.wav', setting('notifier_sound'));
        $this->assertSame('40', (string) setting('notifier_volume'));
    }

    public function testAdminCanSwitchModuleOffWithoutOtherFields(): void
    {
        // Выключенный fieldset не отправляет свои поля — сохранение не должно
        // падать на валидации, а прежние значения обязаны уцелеть
        $admin = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($admin)
            ->post('/admin/notifier-settings', ['sets' => ['notifier_active' => 0]])
            ->assertRedirect(route('notifier.settings'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        Setting::flush();

        $this->assertSame('0', (string) setting('notifier_active'));
        $this->assertSame('60', (string) setting('notifier_interval'));
        $this->assertSame('ding.mp3', setting('notifier_sound'));
    }

    public function testAdminCannotSaveInvalidSettings(): void
    {
        $admin = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($admin)
            ->post('/admin/notifier-settings', [
                'sets' => [
                    'notifier_interval' => 1,
                    'notifier_volume'   => 500,
                    'notifier_sound'    => 'evil.mp3',
                ],
            ])
            ->assertRedirect(route('notifier.settings'))
            ->assertSessionHasErrors([
                'sets[notifier_interval]',
                'sets[notifier_volume]',
                'sets[notifier_sound]',
            ]);

        Setting::flush();

        $this->assertSame('60', (string) setting('notifier_interval'));
    }

    /**
     * Создает непрочитанные или прочитанные диалоги
     */
    private function createDialogues(int $count, bool $read = false): void
    {
        $author = User::factory()->create();

        for ($i = 0; $i < $count; $i++) {
            $message = Message::query()->create([
                'user_id'    => $this->user->id,
                'author_id'  => $author->id,
                'text'       => 'Текст сообщения ' . $i,
                'created_at' => now(),
            ]);

            Dialogue::query()->create([
                'message_id' => $message->id,
                'user_id'    => $this->user->id,
                'author_id'  => $author->id,
                'type'       => Dialogue::IN,
                'reading'    => $read,
                'created_at' => now(),
            ]);
        }
    }
}
