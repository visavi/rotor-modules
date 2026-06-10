<?php

namespace Modules\Backup\Tests\Feature;

use App\Models\User;
use Tests\ModuleTestCase;

class BackupTest extends ModuleTestCase
{
    protected string $moduleName = 'Backup';

    private User $boss;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boss = User::factory()->boss()->create(['login' => 'boss_test']);
        $this->admin = User::factory()->admin()->create(['login' => 'admin_test']);
    }

    public function testBackupIndexRequiresBoss(): void
    {
        $response = $this->get('/admin/backups');
        $response->assertRedirect();
    }

    public function testBackupIndexForbiddenForAdmin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/backups');
        $response->assertForbidden();
    }

    public function testBackupIndexAccessibleByBoss(): void
    {
        $response = $this->actingAs($this->boss)->get('/admin/backups');
        $response->assertOk();
    }
}
