<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
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

        $this->seed(DatabaseSeeder::class);
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

        Setting::forgetSettings();
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
                'id'    => 'G-100',
                'email' => 'john@example.com',
                'name'  => 'John Doe',
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
            'oauth.vk.com/access_token*'  => Http::response([
                'access_token' => 'tok',
                'user_id'      => 999,
                'email'        => 'vk@example.com',
            ]),
            'api.vk.com/method/users.get*' => Http::response([
                'response' => [
                    ['id' => 999, 'screen_name' => 'vkuser'],
                ],
            ]),
        ]);

        $response = $this->hitCallback('vk');

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
                'id'    => 'G-100',
                'email' => 'exist@example.com',
                'name'  => 'Existing',
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
                'id'    => 'G-777',
                'email' => 'link@example.com',
                'name'  => 'Linker',
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
                'id'    => 'G-NOEMAIL',
                'name'  => 'NoMail',
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
