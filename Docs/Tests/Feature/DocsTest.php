<?php

namespace Modules\Docs\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Modules\Docs\Services\DocsService;
use Tests\ModuleTestCase;

class DocsTest extends ModuleTestCase
{
    protected string $moduleName = 'Docs';

    private DocsService $docs;

    protected function setUp(): void
    {
        parent::setUp();

        // Версия в меню тянется с GitHub — в тестах за ней в сеть не ходим
        Http::fake(['*' => Http::response([['tag_name' => 'v14.3.0']])]);

        $this->docs = new DocsService();
    }

    public function testPageIsRendered(): void
    {
        $this->get('/docs/rotor-modules')
            ->assertOk()
            ->assertSee('Модули')
            ->assertSee('module.php');
    }

    public function testDefaultPageIsInstallation(): void
    {
        $this->get('/docs')->assertOk()->assertSee('Установка');
    }

    public function testAnchorsSurviveMarkdown(): void
    {
        $html = $this->docs->render("<a name=\"web-servers\"></a>\n## Web-серверы\n\n<script>alert(1)</script>");

        $this->assertStringContainsString('<a id="web-servers"></a>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testUnknownPageIsNotFound(): void
    {
        // Проверяем только когда документация Laravel загружена: без неё раздел
        // отвечает страницей-заглушкой, а не 404
        if (! file_exists(base_path('modules/Docs/resources/laravel-docs/installation.md'))) {
            $this->markTestSkipped('Документация Laravel не синхронизирована');
        }

        $this->get('/docs/no-such-page')->assertNotFound();
    }

    public function testEveryMenuItemHasItsPage(): void
    {
        $missing = [];

        foreach ($this->docs->getRotorMenu() as $group) {
            foreach ($group['items'] as $item) {
                if (! file_exists(base_path('modules/Docs/resources/docs/' . $item['page'] . '.md'))) {
                    $missing[] = $item['page'];
                }
            }
        }

        $this->assertSame([], $missing, 'В навигации есть ссылки на несуществующие страницы');
    }

    public function testTitleIsTakenFromHeading(): void
    {
        $this->assertSame('Модули', $this->docs->extractTitle("# Модули\n\nтекст"));
        $this->assertNull($this->docs->extractTitle('текст без заголовка'));
    }

    public function testSearchFindsPage(): void
    {
        $results = $this->docs->search('module.php');

        $this->assertNotEmpty($results);
        $this->assertContains('/docs/rotor-modules', array_column($results, 'href'));
    }

    public function testShortQueryIsIgnored(): void
    {
        $this->get('/docs/find?query=мо')
            ->assertOk()
            ->assertSee('Минимум 3 символа');
    }

    public function testSearchPageShowsResults(): void
    {
        $this->get('/docs/find?query=module.php')
            ->assertOk()
            ->assertSee('Найдено:');
    }

    public function testRotorPagesAreOpen(): void
    {
        $this->get('/rotor')->assertOk();
    }
}
