<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Tests\Feature;

use App\Models\User;
use Modules\SocialAuth\Models\Social;
use Tests\ModuleTestCase;

class AdminSocialsTest extends ModuleTestCase
{
    protected string $moduleName = 'SocialAuth';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('app_installed', 1);

        $this->admin = User::factory()->admin()->create(['login' => 'admin_socials']);
    }

    private function makeSocial(User $user, string $provider, ?string $lastLoginAt = null): Social
    {
        return Social::query()->create([
            'user_id'       => $user->id,
            'provider'      => $provider,
            'provider_id'   => $provider . '-' . $user->id,
            'token'         => 'tok',
            'last_login_at' => $lastLoginAt,
        ]);
    }

    public function testIndexShowsLinkedUsers(): void
    {
        $user = User::factory()->create(['login' => 'google_user']);
        $this->makeSocial($user, 'google', now()->toDateTimeString());

        $this->actingAs($this->admin)
            ->get('/admin/socials')
            ->assertOk()
            ->assertSee('google_user')
            ->assertSee('Google');
    }

    public function testIndexFiltersByProvider(): void
    {
        $googleUser = User::factory()->create(['login' => 'google_user']);
        $githubUser = User::factory()->create(['login' => 'github_user']);

        $this->makeSocial($googleUser, 'google');
        $this->makeSocial($githubUser, 'github');

        // assertDontSee не годится: в debug-режиме логин попадает в панель SQL-запросов
        $this->actingAs($this->admin)
            ->get('/admin/socials?provider=github')
            ->assertOk()
            ->assertSee('github_user')
            ->assertViewHas('socials', static fn ($socials) => $socials->pluck('provider')->all() === ['github']);
    }

    public function testUnknownProviderResetsFilter(): void
    {
        $user = User::factory()->create(['login' => 'google_user']);
        $this->makeSocial($user, 'google');

        $this->actingAs($this->admin)
            ->get('/admin/socials?provider=myspace')
            ->assertOk()
            ->assertSee('google_user');
    }

    public function testGuestIsNotAllowed(): void
    {
        $this->get('/admin/socials')->assertRedirect();
    }
}
