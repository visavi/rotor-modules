<?php

namespace Modules\Caches\Tests\Feature;

use App\Models\User;
use Tests\ModuleTestCase;

class CacheTest extends ModuleTestCase
{
    protected string $moduleName = 'Caches';

    private User $boss;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boss = User::factory()->boss()->create(['login' => 'boss_test']);
    }

    public function testIndexRequiresBoss(): void
    {
        $response = $this->get('/admin/caches');
        $response->assertRedirect();
    }

    public function testIndexAccessibleByBoss(): void
    {
        $response = $this->actingAs($this->boss)->get('/admin/caches');
        $response->assertOk();
    }

    public function testIndexViewsTab(): void
    {
        $response = $this->actingAs($this->boss)->get('/admin/caches?type=views');
        $response->assertOk();
    }

    public function testClearRequiresBoss(): void
    {
        $response = $this->post('/admin/caches/clear');
        $response->assertRedirect();
    }

    public function testClearByBoss(): void
    {
        $response = $this->actingAs($this->boss)->post('/admin/caches/clear', ['type' => 'views']);
        $response->assertRedirect(route('admin.caches.index', ['type' => 'views']));
    }
}
