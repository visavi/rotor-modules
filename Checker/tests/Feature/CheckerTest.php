<?php

namespace Modules\Checker\Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\ModuleTestCase;

class CheckerTest extends ModuleTestCase
{
    protected string $moduleName = 'Checker';

    private User $boss;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->boss = User::factory()->boss()->create(['login' => 'boss_test']);
        $this->admin = User::factory()->admin()->create(['login' => 'admin_test']);
    }

    public function testCheckerIndexRequiresBoss(): void
    {
        $response = $this->get('/admin/checkers');
        $response->assertRedirect();
    }

    public function testCheckerIndexForbiddenForAdmin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/checkers');
        $response->assertForbidden();
    }

    public function testCheckerIndexAccessibleByBoss(): void
    {
        $response = $this->actingAs($this->boss)->get('/admin/checkers');
        $response->assertOk();
    }

    public function testCheckerScanRequiresBoss(): void
    {
        $response = $this->post('/admin/checkers/scan');
        $response->assertRedirect();
    }

    public function testCheckerScanForbiddenForAdmin(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/checkers/scan');
        $response->assertForbidden();
    }

    public function testCheckerScanByBossRedirects(): void
    {
        $response = $this->actingAs($this->boss)->post('/admin/checkers/scan');
        $response->assertRedirect('/admin/checkers');
    }
}
