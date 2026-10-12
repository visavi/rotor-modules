<?php

namespace Modules\Feedback\Tests\Feature;

use App\Jobs\SendMailJob;
use App\Models\Dialogue;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Modules\Feedback\Support\Feedback;
use Tests\ModuleTestCase;

class FeedbackTest extends ModuleTestCase
{
    protected string $moduleName = 'Feedback';

    protected function setUp(): void
    {
        parent::setUp();

        // captchaVerify() возвращает true только при отключённой капче
        $this->overrideSetting('captcha_type', 'disable');
        $this->overrideSetting('floodstime', 30);
    }

    public function testGuestSeesNameAndEmail(): void
    {
        $this->get('/feedback')
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false);
    }

    public function testUserSeesOnlyMessage(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/feedback')
            ->assertOk()
            ->assertDontSee('name="email"', false);
    }

    public function testFooterLink(): void
    {
        $this->get('/')->assertSee(route('feedback.index'), false);
    }

    public function testOldAddressRedirects(): void
    {
        $this->get('/mails')->assertRedirect('/feedback')->assertStatus(301);
    }

    public function testRecoveryLinksToFeedback(): void
    {
        $this->get('/recovery')->assertSee(route('feedback.index'), false);
    }

    public function testGuestMessageGoesToOwner(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);
        $admin = User::factory()->create(['level' => User::ADMIN]);

        $this->post('/feedback', [
            'name'    => 'Гость <b>Вася</b>',
            'email'   => 'guest@example.com',
            'message' => '<p>Не работает загрузка</p>',
        ])
            ->assertRedirect('/feedback')
            ->assertSessionHas('success');

        $message = Message::query()->where('user_id', $boss->id)->sole();

        $this->assertSame(0, (int) $message->author_id);
        $this->assertStringContainsString('<p>Не работает загрузка</p>', $message->text);
        $this->assertStringContainsString('<a href="mailto:guest@example.com">guest@example.com</a>', $message->text);
        $this->assertStringContainsString('Гость &lt;b&gt;Вася&lt;/b&gt;', $message->text);

        // Обращения читает владелец, остальные админы их не получают
        $this->assertFalse(Message::query()->where('user_id', $admin->id)->exists());
    }

    public function testUserMessageComesFromUser(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/feedback', ['message' => '<p>Есть предложение</p>'])
            ->assertSessionHas('success');

        $message = Message::query()->where('user_id', $boss->id)->sole();

        // Ответ владельца уйдёт в обычный диалог с пользователем
        $this->assertSame($user->id, (int) $message->author_id);
        $this->assertStringNotContainsString('mailto:', $message->text);

        // У отправителя копии нет: при нескольких владельцах это были бы одинаковые диалоги
        $this->assertFalse(Dialogue::query()->where('user_id', $user->id)->exists());
    }

    public function testOwnerCanTestFormOnHimself(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS, 'email' => null]);
        $coOwner = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($boss)
            ->post('/feedback', ['message' => '<p>Проверка формы</p>'])
            ->assertSessionHas('success');

        // Себе — системным сообщением с логином: диалог с самим собой не открывается
        $own = Message::query()->where('user_id', $boss->id)->sole();
        $this->assertSame(0, (int) $own->author_id);
        $this->assertStringContainsString($boss->login, $own->text);
        $this->assertStringNotContainsString('mailto:', $own->text);

        $this->actingAs($boss)->get('/messages/talk/0')->assertSee('Проверка формы');

        // Второму владельцу — от имени отправителя, как от любого пользователя
        $other = Message::query()->where('user_id', $coOwner->id)->sole();
        $this->assertSame($boss->id, (int) $other->author_id);
    }

    public function testInvalidInputIsRejected(): void
    {
        User::factory()->create(['level' => User::BOSS]);

        $this->post('/feedback', [
            'name'    => 'Я',
            'email'   => 'не-адрес',
            'message' => 'ab',
        ])
            ->assertRedirect('/feedback')
            ->assertSessionHasErrors(['name', 'email', 'message'])
            ->assertSessionHasInput('email', 'не-адрес');

        $this->assertSame(0, Message::query()->count());
    }

    public function testEmailDeliverySendsLetter(): void
    {
        Bus::fake();
        config(['app.email' => 'site@example.com']);
        $this->overrideSetting('feedback_delivery', Feedback::EMAIL);
        User::factory()->create(['level' => User::BOSS]);

        $this->post('/feedback', [
            'name'    => 'Гость',
            'email'   => 'guest@example.com',
            'message' => '<p>Письмо <img src="/uploads/stickers/smile.gif"></p><script>alert(1)</script>',
        ])->assertSessionHas('success');

        Bus::assertDispatched(SendMailJob::class, function (SendMailJob $job) {
            $this->assertSame('site@example.com', $job->data['to']);
            // Ответ из почтового клиента уходит отправителю
            $this->assertSame(['guest@example.com', 'Гость'], $job->data['from']);
            $this->assertStringContainsString('src="' . url('/') . '/uploads/stickers/smile.gif"', $job->data['text']);
            $this->assertStringNotContainsString('<script>', $job->data['text']);

            return true;
        });

        $this->assertSame(0, Message::query()->count());
    }

    public function testEmailDeliveryUsesOwnRecipient(): void
    {
        Bus::fake();
        $this->overrideSetting('feedback_delivery', Feedback::EMAIL);
        $this->overrideSetting('feedback_email', 'support@example.com');

        $this->post('/feedback', ['name' => 'Гость', 'email' => 'guest@example.com', 'message' => '<p>Вопрос</p>']);

        Bus::assertDispatched(SendMailJob::class, static fn (SendMailJob $job) => $job->data['to'] === 'support@example.com');
    }

    public function testEmailDeliveryAsksUserWithoutEmail(): void
    {
        $this->overrideSetting('feedback_delivery', Feedback::EMAIL);

        $this->actingAs(User::factory()->create(['email' => null]))
            ->get('/feedback')
            ->assertSee('name="email"', false)
            ->assertDontSee('name="name"', false);
    }

    public function testEmailDeliveryTakesUserEmailFromProfile(): void
    {
        Bus::fake();
        $this->overrideSetting('feedback_delivery', Feedback::EMAIL);
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->actingAs($user)->get('/feedback')->assertDontSee('name="email"', false);
        $this->actingAs($user)->post('/feedback', ['message' => '<p>Вопрос</p>'])->assertSessionHas('success');

        Bus::assertDispatched(SendMailJob::class, static fn (SendMailJob $job) => $job->data['from'] === ['user@example.com', $user->login]);
    }

    public function testBossSavesSettings(): void
    {
        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->post('/admin/feedback-settings', ['sets' => [
                'feedback_delivery' => Feedback::EMAIL,
                'feedback_email'    => 'support@example.com',
                'logos'             => 'чужой ключ',
            ]])
            ->assertRedirect('/admin/feedback-settings')
            ->assertSessionHas('success');

        $this->assertSame(Feedback::EMAIL, Setting::query()->where('name', 'feedback_delivery')->value('value'));
        $this->assertSame('support@example.com', Setting::query()->where('name', 'feedback_email')->value('value'));
        $this->assertNotSame('чужой ключ', Setting::query()->where('name', 'logos')->value('value'));
    }

    public function testSettingsRejectUnknownDelivery(): void
    {
        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->post('/admin/feedback-settings', ['sets' => ['feedback_delivery' => 'pigeon', 'feedback_email' => 'не-адрес']])
            ->assertSessionHasErrors(['sets[feedback_delivery]', 'sets[feedback_email]']);

        $this->assertNotSame('pigeon', Setting::query()->where('name', 'feedback_delivery')->value('value'));
        $this->assertFalse(Feedback::byEmail());
    }

    public function testSettingsOnlyForBoss(): void
    {
        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->get('/admin/feedback-settings')
            ->assertForbidden();
    }

    public function testFloodIsRejected(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);
        $data = ['name' => 'Гость', 'email' => 'guest@example.com', 'message' => '<p>Первое обращение</p>'];

        $this->post('/feedback', $data)->assertSessionHas('success');
        $this->post('/feedback', $data)->assertSessionHasErrors('message');

        $this->assertSame(1, Message::query()->where('user_id', $boss->id)->count());
    }
}
