<?php

namespace Modules\StyleEditor\Tests\Feature;

use App\Models\User;
use App\Support\Hook;
use Illuminate\Support\Facades\File;
use Tests\ModuleTestCase;

class EditorTest extends ModuleTestCase
{
    protected string $moduleName = 'StyleEditor';

    private User $admin;

    private string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Редактор пишет в public/assets: без подмены тест затёр бы стили рабочего сайта
        $this->publicPath = storage_path('framework/testing/style-editor');
        File::ensureDirectoryExists($this->publicPath . '/assets');
        $this->app->usePublicPath($this->publicPath);

        $this->admin = User::factory()->admin()->create(['login' => 'admin_test']);
    }

    protected function tearDown(): void
    {
        $this->deleteTestingDirectory($this->publicPath);

        parent::tearDown();
    }

    public function testSaveWritesCssAndJs(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/editor', ['css' => 'body { color: red; }', 'js' => 'console.log(1);'])
            ->assertRedirect(route('admin.editor.index'))
            ->assertSessionHas('success');

        $this->assertSame('body { color: red; }', file_get_contents($this->publicPath . '/assets/custom.css'));
        $this->assertSame('console.log(1);', file_get_contents($this->publicPath . '/assets/custom.js'));

        $this->actingAs($this->admin)
            ->get('/admin/editor')
            ->assertSee('body { color: red; }');
    }

    public function testEmptyFieldsClearFiles(): void
    {
        File::put($this->publicPath . '/assets/custom.css', 'body {}');

        $this->actingAs($this->admin)
            ->post('/admin/editor', ['css' => '', 'js' => ''])
            ->assertSessionHas('success');

        $this->assertSame('', file_get_contents($this->publicPath . '/assets/custom.css'));
    }

    public function testArrayInputIsRejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/editor', ['css' => ['body {}'], 'js' => ''])
            ->assertSessionHasErrors('css');

        $this->assertFileDoesNotExist($this->publicPath . '/assets/custom.css');
    }

    public function testHooksIncludeOnlyNonEmptyFiles(): void
    {
        File::put($this->publicPath . '/assets/custom.css', 'body {}');
        File::put($this->publicPath . '/assets/custom.js', '');

        $this->assertStringContainsString('href="/assets/custom.css?v=', Hook::call('head'));
        $this->assertStringNotContainsString('custom.js', Hook::call('footer'));
    }

    public function testModeratorCannotOpenEditor(): void
    {
        $this->actingAs(User::factory()->create(['level' => User::MODER]))
            ->get('/admin/editor')
            ->assertForbidden();
    }

    public function testEditorIndexRequiresAdmin(): void
    {
        $response = $this->get('/admin/editor');
        $response->assertRedirect();
    }

    public function testEditorIndexAccessibleByAdmin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/editor');
        $response->assertOk();
    }

    public function testEditorSaveRequiresAdmin(): void
    {
        $response = $this->post('/admin/editor', ['css' => '', 'js' => '']);
        $response->assertRedirect();
    }
}
