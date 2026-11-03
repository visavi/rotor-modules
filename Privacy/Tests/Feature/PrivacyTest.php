<?php

declare(strict_types=1);

namespace Modules\Privacy\Tests\Feature;

use App\Models\Module;
use App\Models\User;
use App\Services\CaptchaService;
use App\Services\UserService;
use App\Support\Hook;
use App\Support\Registry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Privacy\Models\PrivacyConsent;
use Modules\Privacy\Models\PrivacyText;
use Tests\ModuleTestCase;

class PrivacyTest extends ModuleTestCase
{
    protected string $moduleName = 'Privacy';

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('app_installed', 1);
        $this->overrideSetting('openreg', 1);
        $this->overrideSetting('email_mode', UserService::EMAIL_REQUIRED);
        $this->overrideSetting('captcha_type', 'graphical');
        $this->overrideSetting('title', 'Мой сайт');
    }

    public function testFirstVersionIsFilledFromTemplate(): void
    {
        // Сайт ещё не правил текст — первая версия собирается из шаблона с названием сайта
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('Мой сайт')
            ->assertSee(__('privacy::privacy.operator_placeholder'));

        $version = PrivacyText::query()->sole();

        // Пустая строка между разделами — как двойной Enter в редакторе: абзацы в тексте почти без отступов
        $this->assertSame(5, substr_count($version->rawText(), '<p></p>'));

        $this->get('/privacy')
            ->assertSee(__('privacy::privacy.version', ['id' => $version->id, 'date' => $version->created_at->format('d.m.Y')]));

        $this->assertSame(1, PrivacyText::query()->count());
    }

    public function testTemplateUsesSiteLanguage(): void
    {
        $this->overrideSetting('language', 'ru');
        $visitor = User::factory()->create(['language' => 'en']);

        // Первую версию может собрать посетитель с другим языком — текст всё равно на языке сайта
        $this->actingAs($visitor)->get('/privacy')->assertOk();

        $this->assertStringContainsString(
            'Регистрируясь на сайте',
            PrivacyText::query()->sole()->rawText(),
        );
    }

    public function testLongCyrillicTextIsSaved(): void
    {
        // Кириллица — два байта на символ: допустимый объём не должен упираться в размер колонки
        $text = '<p>' . str_repeat('согласие ', 6000) . '</p>';

        $this->actingAs($this->boss())
            ->post('/admin/privacy', ['text' => $text])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('privacy.admin.index'));

        // Без строгого режима MySQL молча обрезал бы текст — сверяем целиком
        $this->assertSame($text, PrivacyText::query()->latest('id')->firstOrFail()->rawText());
    }

    public function testRegisterFormHasConsentCheckbox(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="privacy"', false)
            ->assertSee('role="switch"', false)
            ->assertSee(route('privacy.index'), false);
    }

    public function testRegistrationRequiresConsent(): void
    {
        $this->withSession(['protect' => 'abc12'])
            ->post('/register', $this->payload())
            ->assertSessionHasErrors('privacy');

        $this->assertDatabaseMissing('users', ['login' => 'newuser']);
    }

    public function testRegistrationStoresConsentToCurrentVersion(): void
    {
        PrivacyText::factory()->create(['text' => '<p>Старая версия</p>']);
        $current = PrivacyText::factory()->create(['text' => '<p>Текущая версия</p>']);

        $this->withSession(['protect' => 'abc12'])
            ->post('/register', $this->payload() + ['privacy' => 1], ['REMOTE_ADDR' => '10.1.2.3'])
            ->assertRedirect('/');

        $user = User::query()->where('login', 'newuser')->firstOrFail();
        $consent = PrivacyConsent::query()->findOrFail($user->id);

        $this->assertSame($current->id, $consent->text_id);
        $this->assertSame('10.1.2.3', $consent->ip);
    }

    public function testApiRegistrationRequiresConsent(): void
    {
        $this->postJson('/api/register', $this->payload() + $this->apiCaptcha())
            ->assertStatus(422)
            ->assertJsonValidationErrors('privacy');

        $this->postJson('/api/register', $this->payload() + $this->apiCaptcha() + ['privacy' => true])
            ->assertStatus(201);

        $user = User::query()->where('login', 'newuser')->firstOrFail();
        $this->assertTrue(PrivacyConsent::query()->whereKey($user->id)->exists());
    }

    public function testApiDocsListConsentField(): void
    {
        Module::factory()->create(['name' => 'Privacy', 'active' => true]);
        Cache::forget('modules');

        $schema = $this->getJson('/api/openapi.json')
            ->assertOk()
            ->json('paths./register.post.requestBody.content.application/json.schema');

        $this->assertSame('boolean', $schema['properties']['privacy']['type']);
        $this->assertContains('privacy', $schema['required']);
        $this->assertContains('login', $schema['required']);
    }

    public function testApiConfigTellsAppToAskConsent(): void
    {
        // Приложение рисует галочку, если в /config есть раздел privacy.
        // Адрес текста не передаётся: это страница /privacy сайта, чей API вызывает приложение
        $config = $this->getJson('/api/config')->assertOk();

        $this->assertSame(['required' => true], $config->json('privacy'));
    }

    public function testSocialButtonsShowConsentNotice(): void
    {
        // Вход через соцсеть заводит аккаунт без формы — согласие даётся по пометке под кнопками
        $notice = Hook::call('socialAuthNotice');

        $this->assertStringContainsString(route('privacy.index'), $notice);
    }

    public function testAnyRegistrationPathStoresConsent(): void
    {
        $user = User::factory()->create();

        foreach (Registry::$onRegisterSave as $handler) {
            $handler($user, Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.9.9.9']));
        }

        $consent = PrivacyConsent::query()->findOrFail($user->id);

        $this->assertSame('10.9.9.9', $consent->ip);
        $this->assertSame(PrivacyText::query()->max('id'), $consent->text_id);
    }

    public function testConsentIsDeletedWithUser(): void
    {
        $user = User::factory()->create();
        PrivacyConsent::factory()->create(['user_id' => $user->id]);

        foreach (Registry::$onDeleteUser as $handler) {
            $handler($user);
        }

        $this->assertFalse(PrivacyConsent::query()->whereKey($user->id)->exists());
    }

    public function testBossPublishesNewVersionAndKeepsOld(): void
    {
        $old = PrivacyText::factory()->create(['text' => '<p>Первая версия</p>']);
        $consent = PrivacyConsent::factory()->create(['text_id' => $old->id]);

        $this->actingAs($this->boss())
            ->post('/admin/privacy', ['text' => '<p>Вторая версия, оператор ИП Иванов</p>'])
            ->assertRedirect(route('privacy.admin.index'))
            ->assertSessionHas('success');

        $this->get('/privacy')
            ->assertSee('Вторая версия, оператор ИП Иванов')
            ->assertDontSee('Первая версия');

        // Доказательство: пользователь соглашался со старой версией, и её текст сохранён
        $this->assertSame($old->id, $consent->fresh()->text_id);

        $this->actingAs($this->boss())
            ->get('/admin/privacy/' . $old->id)
            ->assertOk()
            ->assertSee('Первая версия');
    }

    public function testVersionWithoutConsentsIsEditedInPlace(): void
    {
        $this->travelTo(now()->subDay());
        $first = PrivacyText::factory()->create(['text' => '<p>Оператор: [название]</p>']);
        $this->travelBack();

        $this->actingAs($this->boss())
            ->post('/admin/privacy', ['text' => '<p>Оператор: ИП Иванов</p>'])
            ->assertRedirect(route('privacy.admin.index'))
            ->assertSessionHas('success');

        // Никто ещё не соглашался: доказывать нечего, заполнение шаблона не плодит версию 2
        $this->assertSame(1, PrivacyText::query()->count());
        $first->refresh();
        $this->assertSame('<p>Оператор: ИП Иванов</p>', $first->rawText());
        $this->assertTrue($first->created_at->isToday());
    }

    public function testUnchangedTextDoesNotCreateVersion(): void
    {
        PrivacyText::factory()->create(['text' => '<p>Текст</p>']);

        $this->actingAs($this->boss())
            ->post('/admin/privacy', ['text' => '<p>Текст</p>'])
            ->assertRedirect(route('privacy.admin.index'));

        $this->assertSame(1, PrivacyText::query()->count());
    }

    public function testEditorFormattingDoesNotCreateVersion(): void
    {
        $template = PrivacyText::current();

        // Редактор склеивает абзацы шаблона в строку: «</p>\n<p>» → «</p><p>» — текст тот же
        $this->actingAs($this->boss())
            ->post('/admin/privacy', ['text' => preg_replace('/>\s+</', '><', $template->rawText())])
            ->assertRedirect(route('privacy.admin.index'))
            ->assertSessionHas('info');

        $this->assertSame(1, PrivacyText::query()->count());
    }

    public function testTextIsRequired(): void
    {
        $this->actingAs($this->boss())
            ->post('/admin/privacy', ['text' => ''])
            ->assertSessionHasErrors('text');

        $this->assertSame(0, PrivacyText::query()->count());
    }

    public function testOnlyBossEditsText(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);

        $this->actingAs($admin)->get('/admin/privacy')->assertForbidden();
        $this->actingAs($admin)->post('/admin/privacy', ['text' => '<p>Подмена</p>'])->assertForbidden();

        $this->assertSame(0, PrivacyText::query()->count());
    }

    private function boss(): User
    {
        return User::factory()->create(['level' => User::BOSS]);
    }

    private function payload(): array
    {
        return [
            'login'     => 'newuser',
            'password'  => 'secret123',
            'password2' => 'secret123',
            'email'     => 'newuser@example.com',
            'gender'    => User::MALE,
            'protect'   => 'abc12',
        ];
    }

    /**
     * Капча API сверяется по ключу из кеша, а не по сессии
     */
    private function apiCaptcha(): array
    {
        Cache::put(CaptchaService::CACHE_PREFIX . 'captchakey', 'abc12', CaptchaService::LIFETIME);

        return ['captcha_key' => 'captchakey'];
    }
}
