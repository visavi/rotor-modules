<?php

namespace Modules\PageEditor\Tests\Feature;

use Modules\PageEditor\Support\TranslationRepository;
use Tests\ModuleTestCase;

class TranslationRepositoryTest extends ModuleTestCase
{
    protected string $moduleName = 'PageEditor';

    public function testLocalesComeFromLangDirectories(): void
    {
        $locales = TranslationRepository::locales();

        $this->assertContains('ru', $locales);
        $this->assertContains('en', $locales);
    }

    public function testGroupsIncludeCoreAndModule(): void
    {
        $labels = array_column(TranslationRepository::groups(), 'label');

        $this->assertContains('main', $labels);
        $this->assertContains('page_editor::files', $labels);
    }

    public function testLinesAreFlattenedPerLocale(): void
    {
        $lines = TranslationRepository::lines('page_editor', 'files');

        $this->assertArrayHasKey('file_not_exist', $lines);
        $this->assertSame('Данного файла не существует!', $lines['file_not_exist']['ru']);
    }

    public function testSearchFindsKeyByValue(): void
    {
        $found = TranslationRepository::search('Данного файла не существует');

        $keys = array_column($found, 'key');

        $this->assertContains('file_not_exist', $keys);
    }

    public function testSearchFindsKeyByName(): void
    {
        $found = TranslationRepository::search('file_not_exist');

        $this->assertNotEmpty($found);
    }

    public function testOverlayPathForCoreAndModule(): void
    {
        $this->assertSame(
            resource_path('custom/lang/ru/index.php'),
            TranslationRepository::overlayPath(null, 'index', 'ru'),
        );

        $this->assertSame(
            resource_path('custom/lang/vendor/blog/ru/blog.php'),
            TranslationRepository::overlayPath('blog', 'blog', 'ru'),
        );
    }
}
