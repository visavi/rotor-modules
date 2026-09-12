<?php

namespace Modules\PageEditor\Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\ModuleTestCase;

class PageEditorTest extends ModuleTestCase
{
    protected string $moduleName = 'PageEditor';

    private User $boss;
    private User $admin;

    /**
     * Резервные копии кладём в тестовый каталог, а не в боевой storage
     */
    protected function moduleConfig(): array
    {
        // Конфиг модуля на этот момент ещё не установлен, поэтому корни берём из файла
        $base = include base_path('modules/PageEditor/config.php');
        $roots = $base['roots'] + ['missing' => storage_path('framework/testing/page-editor/missing-root')];

        return [
            'backup_path' => storage_path('framework/testing/page-editor/backups'),
            'roots'       => $roots,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->boss = User::factory()->boss()->create(['login' => 'boss_test']);
        $this->admin = User::factory()->admin()->create(['login' => 'admin_test']);
    }

    protected function tearDown(): void
    {
        foreach (glob(resource_path('views/page_editor_*')) ?: [] as $file) {
            is_dir($file) ? File::deleteDirectory($file) : @unlink($file);
        }

        parent::tearDown();
    }

    public function testMissingRootDirectoryRendersEmptyListing(): void
    {
        // Корень custom не существует до первой правки, но открываться должен
        $this->assertDirectoryDoesNotExist(config('page_editor.roots.missing'));

        $response = $this->actingAs($this->boss)->get('/admin/files?root=missing');

        $response->assertOk();
    }

    public function testMissingRootStillRejectsNestedPath(): void
    {
        $response = $this->actingAs($this->boss)->get('/admin/files?root=missing&path=sub');

        $response->assertNotFound();
    }

    public function testCreateInMissingRootCreatesDirectory(): void
    {
        $missing = config('page_editor.roots.missing');
        $this->assertDirectoryDoesNotExist($missing);

        $response = $this->actingAs($this->boss)->get('/admin/files/create?root=missing');

        $response->assertOk();
        $this->assertDirectoryExists($missing);

        File::deleteDirectory($missing);
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

    public function testModuleConfigIsLoaded(): void
    {
        $roots = config('page_editor.roots');

        $this->assertIsArray($roots);
        $this->assertSame(resource_path('views'), $roots['views']);
        $this->assertSame(base_path('modules'), $roots['modules']);
        $this->assertContains('views', config('page_editor.search_roots'));
        $this->assertContains('blade.php', config('page_editor.editable'));
    }

    public function testFilesIndexListsRootSelector(): void
    {
        $response = $this->actingAs($this->boss)->get(route('admin.files.index'));

        $response->assertOk();
        $response->assertSee('name="root"', false);
        $response->assertViewHas('root', 'views');
    }

    public function testFilesIndexListsModulesRoot(): void
    {
        $response = $this->actingAs($this->boss)
            ->get(route('admin.files.index', ['root' => 'modules']));

        $response->assertOk();
        $response->assertSee('PageEditor');
    }

    public function testFilesIndexRejectsUnknownRoot(): void
    {
        $response = $this->actingAs($this->boss)
            ->get(route('admin.files.index', ['root' => 'vendor']));

        $response->assertNotFound();
    }

    public function testFilesIndexEntriesCarryMetadata(): void
    {
        $response = $this->actingAs($this->boss)
            ->get(route('admin.files.index', ['root' => 'modules', 'path' => 'PageEditor']));

        $response->assertOk();

        $entries = collect($response->viewData('entries'))->keyBy('name');

        $this->assertTrue($entries['module.php']['editable']);
        $this->assertFalse($entries['module.php']['dir']);
        $this->assertGreaterThan(0, $entries['module.php']['lines']);
    }

    public function testEditOpensFileWithExtension(): void
    {
        $response = $this->actingAs($this->boss)->get(route('admin.files.edit', [
            'root' => 'modules',
            'path' => 'PageEditor',
            'file' => 'module.php',
        ]));

        $response->assertOk();
        $response->assertSee('Редактор', false);
    }

    public function testEditRejectsNonEditableExtension(): void
    {
        $response = $this->actingAs($this->boss)->get(route('admin.files.edit', [
            'root' => 'assets',
            'file' => 'nonexistent.png',
        ]));

        $response->assertNotFound();
    }

    public function testCreateAndSaveAndDeleteFileWithDottedName(): void
    {
        $this->actingAs($this->boss)->post(route('admin.files.create', ['root' => 'views']), [
            'filename' => 'page_editor_fixture.blade.php',
        ])->assertRedirect();

        $path = resource_path('views/page_editor_fixture.blade.php');
        $this->assertFileExists($path);

        $this->actingAs($this->boss)->post(route('admin.files.edit', [
            'root' => 'views',
            'file' => 'page_editor_fixture.blade.php',
        ]), ['msg' => 'hello'])->assertRedirect();

        // Файл сохраняется с завершающим переводом строки
        $this->assertSame("hello\n", file_get_contents($path));

        $this->actingAs($this->boss)->delete(route('admin.files.delete'), [
            'root'     => 'views',
            'filename' => 'page_editor_fixture.blade.php',
        ])->assertRedirect();

        $this->assertFileDoesNotExist($path);
    }

    public function testDeleteRemovesDirectoryWithContents(): void
    {
        $dir = resource_path('views/page_editor_dir');
        File::makeDirectory($dir);
        file_put_contents($dir . '/nested.blade.php', 'body');

        $this->actingAs($this->boss)->delete(route('admin.files.delete'), [
            'root'    => 'views',
            'dirname' => 'page_editor_dir',
        ])->assertRedirect();

        $this->assertDirectoryDoesNotExist($dir);
    }

    /**
     * При пустых filename и dirname is_dir("<каталог>/") давало true, валидатор пропускал,
     * и deleteDir() рекурсивно сносил сам текущий каталог — мимо резервных копий
     */
    public function testDeleteWithEmptyNamesKeepsCurrentDirectory(): void
    {
        $dir = resource_path('views/page_editor_keep');
        File::makeDirectory($dir);
        file_put_contents($dir . '/nested.blade.php', 'body');

        $response = $this->actingAs($this->boss)->delete(route('admin.files.delete'), [
            'root'     => 'views',
            'path'     => 'page_editor_keep',
            'filename' => '',
            'dirname'  => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();

        $this->assertDirectoryExists($dir);
        $this->assertFileExists($dir . '/nested.blade.php');
    }

    public function testCreateAcceptsLongMigrationStyleName(): void
    {
        $name = '2026_05_23_000001_create_templates_table.php';

        $this->actingAs($this->boss)->post(route('admin.files.create', ['root' => 'views']), [
            'filename' => $name,
        ])->assertRedirect();

        $this->assertFileExists($path = resource_path('views/' . $name));

        unlink($path);
    }

    public function testRenameMovesFile(): void
    {
        $from = resource_path('views/page_editor_from.blade.php');
        $to = resource_path('views/page_editor_to.blade.php');
        file_put_contents($from, 'body');

        $this->actingAs($this->boss)->post(route('admin.files.rename'), [
            'root'     => 'views',
            'filename' => 'page_editor_from.blade.php',
            'newname'  => 'page_editor_to.blade.php',
        ])->assertRedirect();

        $this->assertFileDoesNotExist($from);
        $this->assertSame('body', file_get_contents($to));
    }

    public function testRenameRejectsNewNameWithSlash(): void
    {
        $from = resource_path('views/page_editor_from.blade.php');
        $traversal = resource_path('views/page_editor_evil.blade.php');
        file_put_contents($from, 'body');

        $this->actingAs($this->boss)->post(route('admin.files.rename'), [
            'root'     => 'views',
            'filename' => 'page_editor_from.blade.php',
            'newname'  => 'page_editor_sub/page_editor_evil.blade.php',
        ])->assertRedirect();

        $this->assertFileExists($from);
        $this->assertFileDoesNotExist($traversal);
    }

    public function testRenameRejectsExistingTargetName(): void
    {
        $from = resource_path('views/page_editor_from.blade.php');
        $existing = resource_path('views/page_editor_existing.blade.php');
        file_put_contents($from, 'from-body');
        file_put_contents($existing, 'existing-body');

        $this->actingAs($this->boss)->post(route('admin.files.rename'), [
            'root'     => 'views',
            'filename' => 'page_editor_from.blade.php',
            'newname'  => 'page_editor_existing.blade.php',
        ])->assertRedirect();

        $this->assertFileExists($from);
        $this->assertSame('from-body', file_get_contents($from));
        $this->assertFileExists($existing);
        $this->assertSame('existing-body', file_get_contents($existing));
    }

    public function testRenameRejectsNotEditableExtension(): void
    {
        $from = resource_path('views/page_editor_from.blade.php');
        file_put_contents($from, 'body');

        $this->actingAs($this->boss)->post(route('admin.files.rename'), [
            'root'     => 'views',
            'filename' => 'page_editor_from.blade.php',
            'newname'  => 'page_editor_from.tpl',
        ])->assertRedirect();

        // Иначе файл уезжает в нередактируемые и открыть его, чтобы вернуть имя, нельзя
        $this->assertFileExists($from);
        $this->assertFileDoesNotExist(resource_path('views/page_editor_from.tpl'));
    }

    public function testRenameAllowsDirectoryWithoutExtension(): void
    {
        $from = resource_path('views/page_editor_dir');
        File::ensureDirectoryExists($from);

        $this->actingAs($this->boss)->post(route('admin.files.rename'), [
            'root'     => 'views',
            'filename' => 'page_editor_dir',
            'newname'  => 'page_editor_dir_renamed',
        ])->assertRedirect();

        $this->assertDirectoryDoesNotExist($from);
        $this->assertDirectoryExists(resource_path('views/page_editor_dir_renamed'));

        File::deleteDirectory(resource_path('views/page_editor_dir_renamed'));
    }

    public function testUploadStoresFile(): void
    {
        $response = $this->actingAs($this->boss)->post(route('admin.files.upload', [
            'root' => 'views',
        ]), [
            'file' => UploadedFile::fake()->create('page_editor_upload.blade.php', 1),
        ]);

        $response->assertRedirect();
        $this->assertFileExists(resource_path('views/page_editor_upload.blade.php'));
    }

    public function testUploadRejectsForbiddenExtension(): void
    {
        $this->actingAs($this->boss)->post(route('admin.files.upload', [
            'root' => 'views',
        ]), [
            'file' => UploadedFile::fake()->create('page_editor_upload.exe', 1),
        ])->assertRedirect();

        $this->assertFileDoesNotExist(resource_path('views/page_editor_upload.exe'));
    }

    public function testUploadKeepsExistingFile(): void
    {
        $existing = resource_path('views/page_editor_upload.blade.php');
        file_put_contents($existing, 'existing-body');

        $this->actingAs($this->boss)->post(route('admin.files.upload', [
            'root' => 'views',
        ]), [
            'file' => UploadedFile::fake()->create('page_editor_upload.blade.php', 1),
        ])->assertRedirect();

        $this->assertSame('existing-body', file_get_contents($existing));
    }

    public function testDeleteKeepsModuleDirectory(): void
    {
        $this->actingAs($this->boss)->delete(route('admin.files.delete'), [
            'root'    => 'modules',
            'dirname' => 'PageEditor',
        ])->assertRedirect();

        // Модуль сносится из админки модулей, иначе останется запись без файлов
        $this->assertDirectoryExists(base_path('modules/PageEditor'));
    }

    public function testRenameKeepsModuleDirectory(): void
    {
        $this->actingAs($this->boss)->post(route('admin.files.rename'), [
            'root'     => 'modules',
            'filename' => 'PageEditor',
            'newname'  => 'PageEditorRenamed',
        ])->assertRedirect();

        $this->assertDirectoryExists(base_path('modules/PageEditor'));
        $this->assertDirectoryDoesNotExist(base_path('modules/PageEditorRenamed'));
    }

    public function testSubdirectoryOfModuleStaysRemovable(): void
    {
        $directory = base_path('modules/PageEditor/page_editor_probe');
        File::ensureDirectoryExists($directory);

        $this->actingAs($this->boss)->delete(route('admin.files.delete'), [
            'root'    => 'modules',
            'path'    => 'PageEditor',
            'dirname' => 'page_editor_probe',
        ])->assertRedirect();

        $this->assertDirectoryDoesNotExist($directory);
    }

    public function testListingHidesSearchForRootWithoutSearch(): void
    {
        // В assets лежат картинки и шрифты: поле уходило бы поиском по шаблонам
        $this->actingAs($this->boss)->get(route('admin.files.index', ['root' => 'assets']))
            ->assertOk()
            ->assertDontSee(__('page_editor::files.search_hint'));
    }

    public function testListingShowsSearchForSearchableRoot(): void
    {
        $this->actingAs($this->boss)->get(route('admin.files.index', ['root' => 'views']))
            ->assertOk()
            ->assertSee(__('page_editor::files.search_hint'));
    }

    public function testListingMarksOrphanOverride(): void
    {
        File::ensureDirectoryExists(resource_path('custom/views/page_editor_probe'));
        File::put(resource_path('custom/views/page_editor_probe/a.blade.php'), 'x');

        $response = $this->actingAs($this->boss)->get(route('admin.files.index', [
            'root' => 'custom',
            'path' => 'views/page_editor_probe',
        ]));

        $response->assertOk();
        $response->assertSee(__('page_editor::files.override_orphan'));

        File::deleteDirectory(resource_path('custom/views/page_editor_probe'));
    }

    public function testDownloadReturnsFile(): void
    {
        $response = $this->actingAs($this->boss)->get(route('admin.files.download', [
            'root' => 'modules',
            'path' => 'PageEditor',
            'file' => 'module.php',
        ]));

        $response->assertOk();
        $response->assertDownload('module.php');
    }

    public function testDownloadMissingFileReturns404(): void
    {
        $response = $this->actingAs($this->boss)->get(route('admin.files.download', [
            'root' => 'views',
            'file' => 'page_editor_missing.blade.php',
        ]));

        $response->assertNotFound();
    }

    public function testEditScrollsToRequestedLine(): void
    {
        $response = $this->actingAs($this->boss)->get(route('admin.files.edit', [
            'root' => 'modules',
            'path' => 'PageEditor',
            'file' => 'module.php',
            'line' => 5,
        ]));

        $response->assertOk();
        $response->assertViewHas('line', 5);
        $response->assertSee('data-goto-line="5"', false);
    }

    public function testEditKeepsTrailingNewline(): void
    {
        $file = resource_path('views/page_editor_newline.blade.php');
        file_put_contents($file, "old\n");

        $this->actingAs($this->boss)->post(route('admin.files.edit', [
            'root' => 'views',
            'file' => 'page_editor_newline.blade.php',
        ]), ['msg' => "first\nsecond"])->assertRedirect();

        // TrimStrings обрезает поле запроса, поэтому перевод строки ставится при сохранении
        $this->assertSame("first\nsecond\n", file_get_contents($file));
    }

    public function testEditConvertsCrlfToLf(): void
    {
        $file = resource_path('views/page_editor_newline.blade.php');
        file_put_contents($file, "old\n");

        $this->actingAs($this->boss)->post(route('admin.files.edit', [
            'root' => 'views',
            'file' => 'page_editor_newline.blade.php',
        ]), ['msg' => "first\r\nsecond\r\n"])->assertRedirect();

        $this->assertSame("first\nsecond\n", file_get_contents($file));
    }
}
