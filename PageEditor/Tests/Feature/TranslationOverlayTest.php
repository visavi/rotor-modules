<?php

namespace Modules\PageEditor\Tests\Feature;

use Modules\PageEditor\Support\PhpArrayDumper;
use Modules\PageEditor\Support\TranslationRepository;
use Tests\ModuleTestCase;

class TranslationOverlayTest extends ModuleTestCase
{
    protected string $moduleName = 'PageEditor';

    /**
     * Боевой resources/custom лежит в git и содержит правки владельца, а
     * storage/app/page-editor/backups — единственный способ откатить сломанный blade,
     * поэтому тесты работают в собственных каталогах. Подмена уезжает в конфиг до
     * включения hooks.php, так что overlay доезжает до translation.loader штатно
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

        $this->deleteTestingDirectory(TranslationRepository::overlayRoot());
    }

    protected function tearDown(): void
    {
        $this->deleteTestingDirectory(TranslationRepository::overlayRoot());

        parent::tearDown();
    }

    public function testDumperProducesShortArraySyntax(): void
    {
        $php = PhpArrayDumper::dump(['a' => 'one', 'b' => ['c' => 'two']]);

        $this->assertStringStartsWith("<?php\n\nreturn [", $php);
        $this->assertStringContainsString("'a' => 'one',", $php);
        $this->assertStringContainsString("'c' => 'two',", $php);
        $this->assertSame(['a' => 'one', 'b' => ['c' => 'two']], eval('?>' . $php));
    }

    public function testSaveWritesOnlyChangedKeys(): void
    {
        $original = TranslationRepository::originals('page_editor', 'files', 'ru');

        TranslationRepository::save('page_editor', 'files', 'ru', [
            'file_not_exist' => 'Нет такого файла',
            'objects'        => $original['objects'],
        ]);

        $path = TranslationRepository::overlayPath('page_editor', 'files', 'ru');
        $written = include $path;

        $this->assertSame(['file_not_exist' => 'Нет такого файла'], $written);
    }

    public function testOverlayOverridesModuleTranslation(): void
    {
        TranslationRepository::save('page_editor', 'files', 'ru', [
            'file_not_exist' => 'Нет такого файла',
        ]);

        app('translator')->setLocale('ru');

        $lines = TranslationRepository::lines('page_editor', 'files');

        $this->assertSame('Нет такого файла', $lines['file_not_exist']['ru']);
    }

    public function testSaveRemovesKeyWhenValueMatchesOriginal(): void
    {
        TranslationRepository::save('page_editor', 'files', 'ru', [
            'file_not_exist' => 'Нет такого файла',
        ]);

        $original = TranslationRepository::originals('page_editor', 'files', 'ru');

        TranslationRepository::save('page_editor', 'files', 'ru', [
            'file_not_exist' => $original['file_not_exist'],
        ]);

        $this->assertFileDoesNotExist(TranslationRepository::overlayPath('page_editor', 'files', 'ru'));
    }

    /**
     * Вьюха рисует отсутствующий в локали ключ пустым полем. Записав такую пустоту в
     * overlay, мы убили бы фолбэк: Translator считает пустую строку валидным переводом
     */
    public function testSaveSkipsEmptyValuesForKeysMissingInOriginals(): void
    {
        TranslationRepository::save('page_editor', 'files', 'en', [
            'file_not_exist'      => 'No such file',
            'page_editor_missing' => '',
        ]);

        $this->assertSame(
            ['file_not_exist' => 'No such file'],
            include TranslationRepository::overlayPath('page_editor', 'files', 'en'),
        );
    }

    /**
     * Пустое значение по ключу, который уже лежит в overlay, — осмысленный сброс
     */
    public function testSaveRemovesOverlayKeyWhenEmptyValueArrives(): void
    {
        TranslationRepository::save('page_editor', 'files', 'en', ['page_editor_extra' => 'Extra']);

        $this->assertSame(
            ['page_editor_extra' => 'Extra'],
            include TranslationRepository::overlayPath('page_editor', 'files', 'en'),
        );

        TranslationRepository::save('page_editor', 'files', 'en', ['page_editor_extra' => '']);

        $this->assertFileDoesNotExist(TranslationRepository::overlayPath('page_editor', 'files', 'en'));
    }

    /**
     * Сброс сильнее содержимого поля: значение приходит прежним, но пара помечена галочкой
     */
    public function testResetRemovesKeyFromOverlay(): void
    {
        app('translator')->setLocale('ru');

        $original = TranslationRepository::originals('page_editor', 'files', 'ru');

        TranslationRepository::save('page_editor', 'files', 'ru', ['file_not_exist' => 'Нет такого файла']);

        $this->assertSame(
            ['ru' => true],
            TranslationRepository::overrides('page_editor', 'files')['file_not_exist'],
        );

        TranslationRepository::save('page_editor', 'files', 'ru', ['file_not_exist' => 'Нет такого файла'], ['file_not_exist']);

        $this->assertFileDoesNotExist(TranslationRepository::overlayPath('page_editor', 'files', 'ru'));
        $this->assertSame($original['file_not_exist'], __('page_editor::files.file_not_exist'));
    }

    /**
     * Ядро регистрирует resources/custom/lang в FileLoader на бутстрапе: без этого
     * правки писались бы в файл, который загрузчик никогда не читает
     */
    public function testCoreRegistersCustomLangPath(): void
    {
        $this->assertContains(
            resource_path('custom/lang'),
            app('translation.loader')->paths(),
        );
    }

    public function testSaveInvalidatesCacheWithinSameRequest(): void
    {
        app('translator')->setLocale('ru');

        // Прогреваем оба кэша — собственный TranslationRepository::lines() и
        // внутренний $loaded транслятора — старым значением, до записи overlay
        $before = TranslationRepository::lines('page_editor', 'files');
        $this->assertSame('Данного файла не существует!', $before['file_not_exist']['ru']);
        $this->assertSame('Данного файла не существует!', __('page_editor::files.file_not_exist'));

        TranslationRepository::save('page_editor', 'files', 'ru', [
            'file_not_exist' => 'Нет такого файла',
        ]);

        // Без сброса кэшей оба чтения ниже вернули бы устаревшее значение
        $after = TranslationRepository::lines('page_editor', 'files');
        $this->assertSame('Нет такого файла', $after['file_not_exist']['ru']);
        $this->assertSame('Нет такого файла', __('page_editor::files.file_not_exist'));
    }
}
