<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Tests\Feature;

use App\Models\BlackList;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Http;
use Modules\SocialAuth\Models\Social;
use Tests\ModuleTestCase;

class RegistrationTest extends ModuleTestCase
{
    protected string $moduleName = 'SocialAuth';

    protected function setUp(): void
    {
        parent::setUp();

        // Незафейканный запрос — ошибка теста, а не молчаливый поход в реальную сеть
        Http::preventStrayRequests();

        $this->seedSettings();
    }

    /**
     * Включает все провайдеры и автолинковку поверх дефолтных настроек
     */
    private function seedSettings(): void
    {
        $settings = [
            'openreg'               => 1,
            'social_autolink_email' => 1,
        ];

        foreach (['google', 'github', 'yandex', 'vk'] as $provider) {
            $settings['social_' . $provider . '_enabled'] = 1;
            $settings['social_' . $provider . '_client_id'] = 'test-id';
            $settings['social_' . $provider . '_client_secret'] = 'test-secret';
        }

        foreach ($settings as $name => $value) {
            Setting::query()->updateOrInsert(['name' => $name], ['value' => $value]);
        }

        clearCache('settings');
    }

    /**
     * Имитирует callback провайдера с валидным state
     */
    private function hitCallback(string $provider): \Illuminate\Testing\TestResponse
    {
        return $this->withSession(['oauth_state' => 'st'])
            ->get("/auth/{$provider}/callback?state=st&code=authcode");
    }

    public function testNewUserRegistersViaGoogle(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token*'           => Http::response(['access_token' => 'tok']),
            'www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id'             => 'G-100',
                'email'          => 'john@example.com',
                'verified_email' => true,
                'name'           => 'John Doe',
            ]),
        ]);

        $response = $this->hitCallback('google');

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'john@example.com')->first();
        $this->assertNotNull($user, 'Пользователь должен быть создан');
        $this->assertSame('john-doe', $user->login);

        $this->assertDatabaseHas('socials', [
            'user_id'     => $user->id,
            'provider'    => 'google',
            'provider_id' => 'G-100',
        ]);
    }

    public function testNewUserRegistersViaGithub(): void
    {
        Http::fake([
            'github.com/login/oauth/access_token*' => Http::response(['access_token' => 'tok']),
            'api.github.com/user*'                 => Http::response([
                'id'    => 555,
                'email' => 'octo@example.com',
                'login' => 'octocat',
            ]),
        ]);

        $response = $this->hitCallback('github');

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'octo@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('socials', [
            'provider'    => 'github',
            'provider_id' => '555',
            'user_id'     => $user->id,
        ]);
    }

    public function testNewUserRegistersViaYandex(): void
    {
        Http::fake([
            'oauth.yandex.ru/token*' => Http::response(['access_token' => 'tok']),
            'login.yandex.ru/info*'  => Http::response([
                'id'            => 'YA-7',
                'default_email' => 'ya@example.com',
                'login'         => 'yauser',
            ]),
        ]);

        $response = $this->hitCallback('yandex');

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ya@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('socials', [
            'provider'    => 'yandex',
            'provider_id' => 'YA-7',
            'user_id'     => $user->id,
        ]);
    }

    public function testNewUserRegistersViaVk(): void
    {
        Http::fake([
            'id.vk.ru/oauth2/auth*' => Http::response([
                'access_token' => 'tok',
            ]),
            'id.vk.ru/oauth2/user_info*' => Http::response([
                'user' => [
                    'user_id'    => 999,
                    'email'      => 'vk@example.com',
                    'first_name' => 'VK',
                    'last_name'  => 'User',
                ],
            ]),
        ]);

        // VK ID присылает device_id в callback, code_verifier лежит в сессии (PKCE)
        $response = $this->withSession(['oauth_state' => 'st', 'oauth_code_verifier' => 'ver'])
            ->get('/auth/vk/callback?state=st&code=authcode&device_id=dev-1');

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'vk@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('socials', [
            'provider'    => 'vk',
            'provider_id' => '999',
            'user_id'     => $user->id,
        ]);
    }

    public function testExistingSocialLogsInWithoutCreatingUser(): void
    {
        $user = User::factory()->create(['email' => 'exist@example.com']);
        Social::query()->create([
            'user_id'     => $user->id,
            'provider'    => 'google',
            'provider_id' => 'G-100',
            'token'       => 'old',
        ]);

        $countBefore = User::query()->count();

        Http::fake([
            'oauth2.googleapis.com/token*'           => Http::response(['access_token' => 'newtok']),
            'www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id'             => 'G-100',
                'email'          => 'exist@example.com',
                'verified_email' => true,
                'name'           => 'Existing',
            ]),
        ]);

        $response = $this->hitCallback('google');

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertSame($countBefore, User::query()->count(), 'Новый пользователь не создаётся');
    }

    public function testAutolinkByEmail(): void
    {
        $user = User::factory()->create(['email' => 'link@example.com']);
        $countBefore = User::query()->count();

        Http::fake([
            'oauth2.googleapis.com/token*'           => Http::response(['access_token' => 'tok']),
            'www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id'             => 'G-777',
                'email'          => 'link@example.com',
                'verified_email' => true,
                'name'           => 'Linker',
            ]),
        ]);

        $response = $this->hitCallback('google');

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertSame($countBefore, User::query()->count());
        $this->assertDatabaseHas('socials', [
            'user_id'     => $user->id,
            'provider'    => 'google',
            'provider_id' => 'G-777',
        ]);
    }

    public function testNoEmailRedirectsToCompleteForm(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token*'           => Http::response(['access_token' => 'tok']),
            'www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id'   => 'G-NOEMAIL',
                'name' => 'NoMail',
            ]),
        ]);

        $response = $this->hitCallback('google');

        $response->assertRedirect(route('social.complete'));
        $this->assertGuest();
        $response->assertSessionHas('social_pending');
        $this->assertDatabaseMissing('socials', ['provider_id' => 'G-NOEMAIL']);
    }

    public function testCompleteCreatesUser(): void
    {
        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->withSession(['social_pending' => [
                'provider'    => 'google',
                'provider_id' => 'G-COMPLETE',
                'token'       => 'tok',
                'name'        => 'Manual Mail',
            ]])
            ->post(route('social.complete.post'), ['email' => 'manual@example.com']);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'manual@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('socials', [
            'user_id'     => $user->id,
            'provider'    => 'google',
            'provider_id' => 'G-COMPLETE',
        ]);
    }

    public function testBlacklistedEmailFromProviderBlocksRegistration(): void
    {
        BlackList::query()->create([
            'type'       => 'email',
            'value'      => 'banned@example.com',
            'user_id'    => 0,
            'created_at' => now()->timestamp,
        ]);

        Http::fake([
            'oauth2.googleapis.com/token*'           => Http::response(['access_token' => 'tok']),
            'www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id'             => 'G-BAN',
                'email'          => 'banned@example.com',
                'verified_email' => true,
                'name'           => 'Banned Guy',
            ]),
        ]);

        $response = $this->hitCallback('google');

        $response->assertRedirect('login');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'banned@example.com']);
        $this->assertDatabaseMissing('socials', ['provider_id' => 'G-BAN']);
    }

    public function testBlacklistedDomainFromProviderBlocksRegistration(): void
    {
        BlackList::query()->create([
            'type'       => 'domain',
            'value'      => 'spam.com',
            'user_id'    => 0,
            'created_at' => now()->timestamp,
        ]);

        Http::fake([
            'oauth2.googleapis.com/token*'           => Http::response(['access_token' => 'tok']),
            'www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id'             => 'G-DOMBAN',
                'email'          => 'guy@spam.com',
                'verified_email' => true,
                'name'           => 'Spam Guy',
            ]),
        ]);

        $response = $this->hitCallback('google');

        $response->assertRedirect('login');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'guy@spam.com']);
        $this->assertDatabaseMissing('socials', ['provider_id' => 'G-DOMBAN']);
    }

    public function testBlacklistedEmailBlocksCompleteForm(): void
    {
        BlackList::query()->create([
            'type'       => 'email',
            'value'      => 'banned@example.com',
            'user_id'    => 0,
            'created_at' => now()->timestamp,
        ]);

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->withSession(['social_pending' => [
                'provider'    => 'google',
                'provider_id' => 'G-BAN2',
                'token'       => 'tok',
                'name'        => 'Manual Mail',
            ]])
            ->post(route('social.complete.post'), ['email' => 'banned@example.com']);

        // Блэклист email режется на уровне валидации CompleteRequest → возврат на форму с ошибкой
        $response->assertRedirect(route('social.complete'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'banned@example.com']);
        $this->assertDatabaseMissing('socials', ['provider_id' => 'G-BAN2']);
    }

    public function testBlacklistedLoginRegeneratesInsteadOfBlocking(): void
    {
        BlackList::query()->create([
            'type'       => 'login',
            'value'      => 'john-doe',
            'user_id'    => 0,
            'created_at' => now()->timestamp,
        ]);

        Http::fake([
            'oauth2.googleapis.com/token*'           => Http::response(['access_token' => 'tok']),
            'www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id'             => 'G-LOGIN',
                'email'          => 'john@example.com',
                'verified_email' => true,
                'name'           => 'John Doe',
            ]),
        ]);

        $response = $this->hitCallback('google');

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'john@example.com')->first();
        $this->assertNotNull($user, 'Пользователь должен быть создан');
        $this->assertSame('john-doe1', $user->login, 'Блэклист-логин должен перегенерироваться');
    }

    public function testInvalidStateRedirectsToLogin(): void
    {
        Http::fake();

        $response = $this->withSession(['oauth_state' => 'real'])
            ->get('/auth/google/callback?state=fake&code=authcode');

        $response->assertRedirect('login');
        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }
}
