<?php

namespace Modules\PageEditor\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Modules\PageEditor\Support\TranslationRepository;
use Tests\ModuleTestCase;

class TranslationEditorTest extends ModuleTestCase
{
    protected string $moduleName = 'PageEditor';

    private User $boss;

    /**
     * Overlay и резервные копии — боевые каталоги, тест работает в собственных.
     * Подмена уезжает в конфиг до включения hooks.php, поэтому overlay доезжает
     * до translation.loader штатным путём
     */
    protected function moduleConfig(): array
    {
        return [
            'overlay_path' => storage_path('framework/testing/page-editor/overlay'),
            'backup_path'  => storage_path('framework/testing/page-editor/backups'),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ядро зарегистрировало боевой resources/custom/lang, а тест работает
        // во временном каталоге — добавляем его тем же способом
        app('translation.loader')->addPath(TranslationRepository::overlayRoot());

        $this->boss = User::factory()->boss()->create(['login' => 'boss_trans']);
        $this->deleteTestingDirectory(TranslationRepository::overlayRoot());
    }

    protected function tearDown(): void
    {
        $this->deleteTestingDirectory(TranslationRepository::overlayRoot());

        parent::tearDown();
    }

    public function testTranslationsPageRequiresBoss(): void
    {
        $this->get(route('admin.files.translations'))->assertRedirect();
    }

    public function testTranslationsPageListsGroups(): void
    {
        $response = $this->actingAs($this->boss)->get(route('admin.files.translations'));

        $response->assertOk();
        $response->assertSee('page_editor::files');
    }

    public function testTranslationsSearchFindsKeyByValue(): void
    {
        $response = $this->actingAs($this->boss)->get(route('admin.files.translations', [
            'query' => 'Данного файла не существует',
        ]));

        $response->assertOk();
        $response->assertSee('file_not_exist');
    }

    public function testSaveWritesOverlay(): void
    {
        $this->actingAs($this->boss)->post(route('admin.files.translations.save'), [
            'namespace' => 'page_editor',
            'group'     => 'files',
            'lines'     => [
                'file_not_exist' => ['ru' => 'Нет такого файла'],
            ],
        ])->assertRedirect();

        $written = include TranslationRepository::overlayPath('page_editor', 'files', 'ru');

        $this->assertSame('Нет такого файла', $written['file_not_exist']);
    }

    /**
     * Переопределённый ключ помечается и получает чекбокс сброса
     */
    public function testOverriddenKeyIsMarkedWithResetCheckbox(): void
    {
        TranslationRepository::save('page_editor', 'files', 'ru', [
            'file_not_exist' => 'Нет такого файла',
        ]);

        $response = $this->actingAs($this->boss)->get(route('admin.files.translations', [
            'group' => 'page_editor::files',
        ]));

        $response->assertOk();
        $response->assertSee('name="reset[file_not_exist][ru]"', false);
        $response->assertSee('border-warning', false);
    }

    /**
     * Сброс через форму удаляет ключ из overlay и возвращает исходное значение
     */
    public function testSaveResetsOverriddenKey(): void
    {
        TranslationRepository::save('page_editor', 'files', 'ru', [
            'file_not_exist' => 'Нет такого файла',
        ]);

        $original = TranslationRepository::originals('page_editor', 'files', 'ru');

        $this->actingAs($this->boss)->post(route('admin.files.translations.save'), [
            'namespace' => 'page_editor',
            'group'     => 'files',
            'lines'     => [
                'file_not_exist' => ['ru' => 'Нет такого файла'],
            ],
            'reset' => [
                'file_not_exist' => ['ru' => '1'],
            ],
        ])->assertRedirect();

        $written = include TranslationRepository::overlayPath('page_editor', 'files', 'ru');

        $this->assertArrayNotHasKey('file_not_exist', $written);

        app('translator')->setLocale('ru');
        $this->assertSame($original['file_not_exist'], __('page_editor::files.file_not_exist'));
    }

    /**
     * Локаль в reset проверяется тем же белым списком, что и в lines
     */
    public function testSaveRejectsForgedLocaleInReset(): void
    {
        $this->actingAs($this->boss)->post(route('admin.files.translations.save'), [
            'namespace' => 'page_editor',
            'group'     => 'files',
            'reset'     => [
                'file_not_exist' => ['../../../../../../public/shell' => '1'],
            ],
        ])->assertNotFound();
    }

    /**
     * namespace/group подделываются в "../../" — normalize() схлопывает traversal, а
     * оставшееся значение не проходит по regex |^[a-z0-9_]+$|, поэтому запрос должен
     * получить 404 и overlay-файл вне resources/custom/lang не должен появиться
     */
    public function testSaveRejectsForgedNamespaceAndDoesNotEscapeOverlay(): void
    {
        $outsideFile = base_path('resources/lang/ru/forged.php');
        File::delete($outsideFile);

        $response = $this->actingAs($this->boss)->post(route('admin.files.translations.save'), [
            'namespace' => '../../resources/lang',
            'group'     => 'forged',
            'lines'     => [
                'pwned' => ['ru' => 'hacked'],
            ],
        ]);

        $response->assertNotFound();
        $this->assertFileDoesNotExist($outsideFile);
        $this->assertDirectoryDoesNotExist(TranslationRepository::overlayRoot());
    }

    /**
     * То же самое для group: "../.." в этом поле тоже должно отклоняться, а не только
     * в namespace
     */
    public function testSaveRejectsForgedGroup(): void
    {
        $response = $this->actingAs($this->boss)->post(route('admin.files.translations.save'), [
            'namespace' => 'page_editor',
            'group'     => '../../resources/lang/ru/forged',
            'lines'     => [
                'pwned' => ['ru' => 'hacked'],
            ],
        ]);

        $response->assertNotFound();
        $this->assertDirectoryDoesNotExist(TranslationRepository::overlayRoot());
    }

    /**
     * locale приходит ключом массива lines[ключ][локаль] и, в отличие от namespace/group,
     * не прогонялся через PathResolver/regex — уходил прямо в overlayPath(), который
     * склеивает из него путь к файлу, а FileWriter::put() сам создаёт недостающие каталоги.
     * Подделанное значение вида "../../../../../../public/shell" должно отклоняться
     * белым списком TranslationRepository::locales() и не создавать файл за пределами
     * resources/custom/lang
     */
    public function testSaveRejectsForgedLocaleAndDoesNotEscapeOverlay(): void
    {
        $forgedLocale = '../../../../../../public/shell';
        $outsideFile = TranslationRepository::overlayPath('page_editor', 'files', $forgedLocale);
        File::delete($outsideFile);

        $response = $this->actingAs($this->boss)->post(route('admin.files.translations.save'), [
            'namespace' => 'page_editor',
            'group'     => 'files',
            'lines'     => [
                'file_not_exist' => [$forgedLocale => '<?php system($_GET["c"]); '],
            ],
        ]);

        $response->assertNotFound();
        $this->assertFileDoesNotExist($outsideFile);
        $this->assertDirectoryDoesNotExist(TranslationRepository::overlayRoot());
    }
}
