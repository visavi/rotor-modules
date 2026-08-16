<?php

namespace Modules\Phpinfo\Tests\Feature;

use App\Models\User;
use Tests\ModuleTestCase;

class PhpinfoSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Phpinfo';

    public function testPageIsOpenForAdmin(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);

        $this->actingAs($admin)
            ->get('/admin/phpinfo')
            ->assertOk()
            ->assertSee(PHP_VERSION);
    }

    public function testModeratorIsRejected(): void
    {
        $moderator = User::factory()->create(['level' => User::MODER]);

        $this->actingAs($moderator)->get('/admin/phpinfo')->assertForbidden();
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->get('/admin/phpinfo')->assertRedirect();
    }
}
