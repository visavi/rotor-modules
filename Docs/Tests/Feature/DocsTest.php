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

        // Версия в меню тянется с GitHub — в тестах за ней в сеть не ходим.
        // Заглушка адресная: wildcard перехватывал бы и запросы реестров модулей
        Http::fake(['api.github.com/*' => Http::response([['tag_name' => 'v14.3.0']])]);

        $this->docs = new DocsService();
    }

    public function testModulesCatalogShowsReleaseDate(): void
    {
        \App\Models\ModuleRegistry::query()->truncate();

        Http::fake([
            '*' => Http::response([
                'name'    => 'Test Registry',
                'modules' => [
                    [
                        'module'   => 'Dated',
                        'name'     => 'Модуль с датой',
                        'versions' => [
                            ['version' => '1.1.0', 'requires' => '', 'released_at' => '2020-01-02'],
                            ['version' => '1.0.0', 'requires' => '', 'released_at' => '2019-03-04'],
                        ],
                    ],
                    [
                        'module'   => 'Undated',
                        'name'     => 'Модуль без даты',
                        'versions' => [
                            ['version' => '1.0.0', 'requires' => ''],
                        ],
                    ],
                ],
            ]),
        ]);

        \App\Models\ModuleRegistry::query()->create(['url' => 'https://registry.example.com/modules.json', 'active' => true]);

        $response = $this->get('/rotor/modules');

        $response->assertOk();
        $response->assertSee('data-released="2020-01-02"', false);
        $response->assertSee('02.01.2020');
        // Дата есть и в списке прошлых версий
        $response->assertSee('04.03.2019');
        // Версия без released_at остаётся без даты — в сортировке уходит в конец
        $response->assertSee('data-released=""', false);
        $response->assertSee('<option value="released">', false);
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
