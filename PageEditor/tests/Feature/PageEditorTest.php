<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\ModuleTestCase;

class PageEditorTest extends ModuleTestCase
{
    protected string $moduleName = 'PageEditor';

    private User $boss;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->boss = User::factory()->boss()->create(['login' => 'boss_test']);
        $this->admin = User::factory()->admin()->create(['login' => 'admin_test']);
    }

    public function testFilesIndexRequiresBoss(): void
    {
        $response = $this->get('/admin/files');
        $response->assertRedirect();
    }

    public function testFilesIndexForbiddenForAdmin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/files');
        $response->assertForbidden();
    }

    public function testFilesIndexAccessibleByBoss(): void
    {
        $response = $this->actingAs($this->boss)->get('/admin/files');
        $response->assertOk();
    }
}
