<?php

namespace Modules\Caches\Tests\Feature;

use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
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

    public function testThumbnailsTabShowsSummaryAndClears(): void
    {
        $publicPath = storage_path('framework/testing/public-' . uniqid());
        $this->app->usePublicPath($publicPath);
        $this->beforeApplicationDestroyed(static fn () => File::deleteDirectory($publicPath));

        File::ensureDirectoryExists(public_path('uploads/thumbnails/700/forums'));
        File::put(public_path('uploads/thumbnails/700/forums/a.jpg'), str_repeat('x', 2048));
        File::put(public_path('uploads/thumbnails/700/forums/b.jpg'), str_repeat('x', 2048));
        File::ensureDirectoryExists(public_path('uploads/forums'));
        File::put(public_path('uploads/forums/a.jpg'), 'original');

        $this->actingAs($this->boss)
            ->get('/admin/caches?type=thumbnails')
            ->assertOk()
            ->assertSee(__('caches::caches.thumbnails_count', ['count' => 2]))
            ->assertSee(formatSize(4096));

        $this->actingAs($this->boss)
            ->post('/admin/caches/clear', ['type' => 'thumbnails'])
            ->assertRedirect(route('admin.caches.index', ['type' => 'thumbnails']));

        // Удаляются только превью, оригиналы остаются
        $this->assertDirectoryDoesNotExist(public_path('uploads/thumbnails'));
        $this->assertFileExists(public_path('uploads/forums/a.jpg'));
    }

    public function testThumbnailsClearFailureIsReported(): void
    {
        $publicPath = storage_path('framework/testing/public-' . uniqid());
        $this->app->usePublicPath($publicPath);
        // Фасад ниже подменён — убираем каталог напрямую
        $this->beforeApplicationDestroyed(static fn () => (new Filesystem())->deleteDirectory($publicPath));

        File::ensureDirectoryExists(public_path('uploads/thumbnails/700'));

        // Root в контейнере удалит всё, поэтому отказ — как при чужих правах на хостинге — подменяем
        File::partialMock()->shouldReceive('deleteDirectory')->once()->andReturnFalse();

        $this->actingAs($this->boss)
            ->post('/admin/caches/clear', ['type' => 'thumbnails'])
            ->assertSessionHas('danger', __('caches::caches.clear_failed'))
            ->assertSessionMissing('success');
    }
}
