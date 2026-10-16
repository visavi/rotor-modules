<?php

namespace Modules\MailNotify\Tests\Feature;

use App\Jobs\SendMailJob;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\MailNotify\Console\AddBirthdays;
use RuntimeException;
use Tests\ModuleTestCase;

class MailNotifyTest extends ModuleTestCase
{
    protected string $moduleName = 'MailNotify';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(Kernel::class)->registerCommand(new AddBirthdays());
    }

    public function testBirthdaysAreQueued(): void
    {
        Queue::fake();

        $this->makeBirthdayUser(1);

        $this->artisan('add:birthdays')->assertSuccessful();

        Queue::assertPushed(SendMailJob::class, static function (SendMailJob $job) {
            return $job->view === 'mail_notify::mailer.birthday'
                && $job->data['to'] === 'birthday1@example.com'
                && $job->data['username'] === 'Birthday 1'
                && $job->data['unsubscribe'] === 'key-1';
        });
    }

    public function testBirthdayMailRenders(): void
    {
        $data = ['subject' => 'Subject', 'username' => 'Birthday 1', 'unsubscribe' => 'key-1'];

        foreach (['mail_notify::mailer.birthday', 'mail_notify::mailer.text.birthday'] as $view) {
            $html = view($view, $data)->render();

            $this->assertStringContainsString('Birthday 1', $html);
            $this->assertStringContainsString(url('/unsubscribe?key=key-1'), $html);
        }
    }

    public function testPacketSizeStaggersDelivery(): void
    {
        Queue::fake();

        $this->overrideSetting('sendmailpacket', 2);

        foreach (range(1, 5) as $index) {
            $this->makeBirthdayUser($index);
        }

        $this->artisan('add:birthdays')->assertSuccessful();

        Queue::assertPushed(SendMailJob::class, 5);

        Queue::assertPushed(SendMailJob::class, static function (SendMailJob $job) {
            return $job->delay !== null;
        });
    }

    public function testSyncFailureDoesNotStopBirthdays(): void
    {
        // На sync письмо уходит прямо в команде: сбой на одном адресе
        // не должен оставлять без поздравления остальных
        $sent = [];
        $parts = [];

        Mail::shouldReceive('send')->andReturnUsing(static function (array $views, array $data) use (&$sent, &$parts) {
            if ($data['to'] === 'birthday1@example.com') {
                throw new RuntimeException('Connection could not be established');
            }

            $sent[] = $data['to'];
            $parts = array_keys($views);
        });

        foreach (range(1, 3) as $index) {
            $this->makeBirthdayUser($index);
        }

        try {
            $this->artisan('add:birthdays')->assertSuccessful();
        } finally {
            // Метка сбоя живёт файлом и пережила бы тест
            @unlink(storage_path('framework/mail-failure'));
        }

        sort($sent);

        $this->assertSame(['birthday2@example.com', 'birthday3@example.com'], $sent);

        // Текстовую версию шаблона модуля ядро находит в его mailer/text
        $this->assertSame(['html', 'raw'], $parts);
    }

    public function testUnsubscribe(): void
    {
        $user = User::factory()->create(['subscribe' => 'subscribe-key-test']);

        $this->get('/unsubscribe?key=subscribe-key-test')
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $this->assertNull($user->fresh()->subscribe);
    }

    public function testUnsubscribeWithUnknownKeyIsRejected(): void
    {
        $this->get('/unsubscribe?key=nonexistent')
            ->assertOk()
            ->assertSee(__('mail_notify::mail_notify.token_expired'));
    }

    public function testSettingsShowSubscription(): void
    {
        $user = User::factory()->create(['subscribe' => Str::random(32)]);

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertSee('name="subscribe"', false);
    }

    public function testResavingKeepsUnsubscribeKey(): void
    {
        $user = User::factory()->create(['subscribe' => 'old-key']);

        $this->actingAs($user)
            ->post('/settings', $this->settings(['subscribe' => 1]))
            ->assertSessionHas('success');

        // Ссылки отписки из уже отправленных писем продолжают работать
        $this->assertSame('old-key', $user->fresh()->subscribe);
    }

    public function testSubscriptionIsSwitched(): void
    {
        $user = User::factory()->create(['subscribe' => null]);

        $this->actingAs($user)->post('/settings', $this->settings(['subscribe' => 1]));
        $this->assertNotNull($user->fresh()->subscribe);

        $this->actingAs($user)->post('/settings', $this->settings(['subscribe' => 0]));
        $this->assertNull($user->fresh()->subscribe);
    }

    public function testApiWithoutFieldKeepsSubscription(): void
    {
        $user = User::factory()->create(['subscribe' => 'old-key', 'apikey' => Str::random(32)]);
        $headers = ['Authorization' => 'Bearer ' . $user->apikey];

        $this->patchJson('/api/user/settings', ['language' => 'ru', 'timezone' => 0, 'themes' => 'default'], $headers)->assertOk();
        $this->assertSame('old-key', $user->fresh()->subscribe);

        $this->patchJson('/api/user/settings', ['language' => 'ru', 'timezone' => 0, 'themes' => 'default', 'subscribe' => false], $headers)->assertOk();
        $this->assertNull($user->fresh()->subscribe);
    }

    public function testBossSavesSettings(): void
    {
        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->post('/admin/mail-notify-settings', ['sets' => ['sendprivatmailday' => 5, 'sendmailpacket' => 10, 'logos' => 'чужой ключ']])
            ->assertRedirect('/admin/mail-notify-settings')
            ->assertSessionHas('success');

        $this->assertSame('5', Setting::query()->where('name', 'sendprivatmailday')->value('value'));
        $this->assertSame('10', Setting::query()->where('name', 'sendmailpacket')->value('value'));
        $this->assertNotSame('чужой ключ', Setting::query()->where('name', 'logos')->value('value'));
    }

    public function testSettingsRejectOutOfRange(): void
    {
        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->post('/admin/mail-notify-settings', ['sets' => ['sendprivatmailday' => 0, 'sendmailpacket' => 5000]])
            ->assertSessionHasErrors(['sets[sendprivatmailday]', 'sets[sendmailpacket]']);
    }

    /**
     * Полный набор полей формы настроек
     *
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function settings(array $extra): array
    {
        return ['themes' => 'default', 'language' => 'ru', 'timezone' => 0] + $extra;
    }

    private function makeBirthdayUser(int $index): void
    {
        User::factory()->create([
            'login'     => 'birthday_' . $index,
            'name'      => 'Birthday ' . $index,
            'email'     => 'birthday' . $index . '@example.com',
            'birthday'  => now()->format('d.m.Y'),
            'point'     => 10,
            'subscribe' => 'key-' . $index,
        ]);
    }
}
