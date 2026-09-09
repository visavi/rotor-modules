<?php

declare(strict_types=1);

namespace Modules\PageEditor\Tests\Feature;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Modules\PageEditor\Support\OverrideResolver;
use Tests\ModuleTestCase;

class OverrideResolverTest extends ModuleTestCase
{
    protected string $moduleName = 'PageEditor';

    protected function tearDown(): void
    {
        File::deleteDirectory(resource_path('custom/views/page_editor_probe'));
        File::deleteDirectory(resource_path('views/page_editor_probe'));

        parent::tearDown();
    }

    private function probe(string $content = 'original'): void
    {
        File::ensureDirectoryExists(resource_path('views/page_editor_probe'));
        File::put(resource_path('views/page_editor_probe/a.blade.php'), $content);
    }

    public function testOverrideForCoreTemplate(): void
    {
        $override = OverrideResolver::override('views', 'admin/rules', 'index.blade.php');

        $this->assertSame('custom', $override['root']);
        $this->assertSame('views/admin/rules', $override['path']);
        $this->assertSame('index.blade.php', $override['file']);
    }

    public function testOverrideForModuleTemplate(): void
    {
        $override = OverrideResolver::override('modules', 'PageEditor/resources/views/admin/files', 'edit.blade.php');

        $this->assertSame('views/page_editor/admin/files', $override['path']);
    }

    public function testOverrideForModuleTranslation(): void
    {
        $override = OverrideResolver::override('modules', 'PageEditor/resources/lang/ru', 'files.php');

        $this->assertSame('lang/vendor/page_editor/ru', $override['path']);
    }

    /**
     * У кода модуля механизма подмены нет, поэтому и правку предлагать нельзя
     */
    public function testModulePhpHasNoOverride(): void
    {
        $this->assertNull(OverrideResolver::override('modules', 'PageEditor/Support', 'PathResolver.php'));
        $this->assertNull(OverrideResolver::override('assets', 'img', 'logo.png'));
    }

    public function testOriginalForCoreTemplate(): void
    {
        $original = OverrideResolver::original('views/admin/rules', 'index.blade.php');

        $this->assertSame('views', $original['root']);
        $this->assertSame('admin/rules', $original['path']);
    }

    public function testOriginalForModuleTemplate(): void
    {
        $original = OverrideResolver::original('views/page_editor/admin/files', 'edit.blade.php');

        $this->assertSame('modules', $original['root']);
        $this->assertSame('PageEditor/resources/views/admin/files', $original['path']);
    }

    public function testOriginalForModuleTranslation(): void
    {
        $original = OverrideResolver::original('lang/vendor/page_editor/ru', 'files.php');

        $this->assertSame('modules', $original['root']);
        $this->assertSame('PageEditor/resources/lang/ru', $original['path']);
    }

    /**
     * themes — каталог ядра, а не ключ модуля
     */
    public function testOriginalForThemeTemplate(): void
    {
        $original = OverrideResolver::original('views/themes/default', 'footer.blade.php');

        $this->assertSame('views', $original['root']);
        $this->assertSame('themes/default', $original['path']);
    }

    public function testExistsReflectsFile(): void
    {
        $override = OverrideResolver::override('views', 'page_editor_probe', 'a.blade.php');
        $this->assertFalse($override['exists']);

        File::ensureDirectoryExists(resource_path('custom/views/page_editor_probe'));
        File::put(resource_path('custom/views/page_editor_probe/a.blade.php'), 'x');

        $override = OverrideResolver::override('views', 'page_editor_probe', 'a.blade.php');
        $this->assertTrue($override['exists']);
    }

    public function testEditorShowsBannerWhenOverrideExists(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_override']);

        File::ensureDirectoryExists(resource_path('custom/views/page_editor_probe'));
        File::put(resource_path('custom/views/page_editor_probe/a.blade.php'), 'x');
        File::ensureDirectoryExists(resource_path('views/page_editor_probe'));
        File::put(resource_path('views/page_editor_probe/a.blade.php'), 'original');

        $response = $this->actingAs($boss)->get(route('admin.files.edit', [
            'root' => 'views',
            'path' => 'page_editor_probe',
            'file' => 'a.blade.php',
        ]));

        $response->assertOk();
        $response->assertSee(__('page_editor::files.override_link'));

        File::deleteDirectory(resource_path('views/page_editor_probe'));
    }

    public function testSaveToCustomKeepsOriginalUntouched(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_save_custom']);
        $this->probe();

        $this->actingAs($boss)->post(route('admin.files.edit', [
            'root' => 'views',
            'path' => 'page_editor_probe',
            'file' => 'a.blade.php',
        ]), ['msg' => 'changed', 'target' => 'custom'])
            ->assertRedirect(route('admin.files.edit', [
                'root' => 'custom',
                'path' => 'views/page_editor_probe',
                'file' => 'a.blade.php',
            ]));

        $this->assertSame("changed\n", file_get_contents(resource_path('custom/views/page_editor_probe/a.blade.php')));
        $this->assertSame('original', file_get_contents(resource_path('views/page_editor_probe/a.blade.php')));
    }

    public function testSaveWithoutTargetWritesOriginal(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_save_original']);
        $this->probe();

        $this->actingAs($boss)->post(route('admin.files.edit', [
            'root' => 'views',
            'path' => 'page_editor_probe',
            'file' => 'a.blade.php',
        ]), ['msg' => 'changed', 'target' => 'original'])->assertRedirect();

        $this->assertSame("changed\n", file_get_contents(resource_path('views/page_editor_probe/a.blade.php')));
        $this->assertFileDoesNotExist(resource_path('custom/views/page_editor_probe/a.blade.php'));
    }

    /**
     * Подделанный target у непереопределяемого файла не должен уводить запись в сторону
     */
    public function testSaveToCustomIgnoredForNonOverridableFile(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_save_forged']);
        $file = public_path('assets/page_editor_probe.css');
        File::put($file, 'a{}');

        $this->actingAs($boss)->post(route('admin.files.edit', [
            'root' => 'assets',
            'file' => 'page_editor_probe.css',
        ]), ['msg' => 'b{}', 'target' => 'custom'])->assertRedirect();

        $this->assertSame("b{}\n", file_get_contents($file));

        File::delete($file);
    }

    public function testEditorWarnsAboutModuleFile(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_module_warn']);

        $response = $this->actingAs($boss)->get(route('admin.files.edit', [
            'root' => 'modules',
            'path' => 'PageEditor/Support',
            'file' => 'PathResolver.php',
        ]));

        $response->assertOk();
        $response->assertSee(__('page_editor::files.module_file_warning'));

        // Подсказка про «Мои правки» только там, где переопределение возможно
        $response->assertDontSee(__('page_editor::files.module_file_hint'));
    }

    public function testEditorSuggestsOverrideForModuleTemplate(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_module_hint']);

        $response = $this->actingAs($boss)->get(route('admin.files.edit', [
            'root' => 'modules',
            'path' => 'PageEditor/resources/views/admin/files',
            'file' => 'edit.blade.php',
        ]));

        $response->assertOk();
        $response->assertSee(__('page_editor::files.module_file_hint'));
    }

    public function testModulesListMarksEnabledAndDisabled(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_module_icons']);

        // Список активных модулей берётся из БД, в тестовой базе он пуст
        Module::query()->create(['name' => 'PageEditor', 'version' => '1.0.0', 'active' => true]);
        cache()->forget('modules');

        $response = $this->actingAs($boss)->get(route('admin.files.index', ['root' => 'modules']));

        $response->assertOk();
        $response->assertSee(__('page_editor::files.module_enabled'));
        $response->assertSee(__('page_editor::files.module_disabled'));
    }
}
