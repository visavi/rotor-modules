<?php

namespace Modules\HomeBuilder\Tests\Feature;

use App\Models\Feed;
use App\Models\User;
use App\Support\Registry;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Modules\HomeBuilder\Models\Block;
use Tests\ModuleTestCase;

class HomeBuilderTest extends ModuleTestCase
{
    protected string $moduleName = 'HomeBuilder';

    private User $admin;

    private User $boss;

    /**
     * @var list<string>
     */
    private array $templateFiles = [];

    private bool $templateDirExisted;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('homepage', 'home-builder');

        // Каталог шаблонов создаёт и форма блока, и сам тест — после теста его быть не должно
        $this->templateDirExisted = is_dir(Block::templatePath());

        // Раздел ленты без модуля: записи — строки comments, карточка — временный шаблон
        $cards = sys_get_temp_dir() . '/rotor-test-home-builder';
        if (! is_dir($cards)) {
            mkdir($cards);
        }
        file_put_contents($cards . '/_entry.blade.php', '<div class="test-card">Карточка: {{ $post->title }}</div>');
        View::addNamespace('home_builder_test', $cards);

        Relation::morphMap([TestEntry::$morphName => TestEntry::class]);
        Registry::setLabel(TestEntry::$morphName, 'Тестовый раздел');
        Registry::feed(TestEntry::class, ['with' => ['user'], 'view' => 'home_builder_test::_entry']);

        $this->admin = User::factory()->admin()->create();
        $this->boss = User::factory()->boss()->create();
    }

    protected function tearDown(): void
    {
        foreach ($this->templateFiles as $file) {
            @unlink($file);
        }

        if (! $this->templateDirExisted) {
            @rmdir(Block::templatePath());
        }

        parent::tearDown();
    }

    public function testHomepageShowsActiveBlocksInOrder(): void
    {
        $this->createEntry('Свежая запись');
        $this->createBlock(['type' => Block::TEXT, 'title' => 'Приветствие', 'text' => '<p>Добро пожаловать</p>', 'sort' => 1]);
        $this->createBlock(['type' => Block::ENTRIES, 'title' => 'Новое', 'sources' => [TestEntry::$morphName], 'layout' => 'list', 'sort' => 0]);
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Блок-невидимка</p>', 'active' => false, 'sort' => 2]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Новое', 'Свежая запись', 'Приветствие', 'Добро пожаловать'])
            ->assertDontSee('Блок-невидимка');
    }

    public function testEntriesShowLatestRecordsOfSelectedSources(): void
    {
        $this->createEntry('Старая запись', now()->subDays(3));
        $this->createEntry('Средняя запись', now()->subDays(2));
        $this->createEntry('Новая запись', now()->subDay());
        $this->createBlock(['type' => Block::ENTRIES, 'sources' => [TestEntry::$morphName], 'layout' => 'list', 'items' => 2]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Новая запись', 'Средняя запись'])
            ->assertSee('href="/test-entries/', false)
            ->assertDontSee('Старая запись');
    }

    public function testEntriesLayouts(): void
    {
        $this->createEntry('Запись без картинки');
        $this->createBlock(['type' => Block::ENTRIES, 'title' => 'Подробно', 'sources' => [TestEntry::$morphName], 'layout' => 'details']);
        $this->createBlock(['type' => Block::ENTRIES, 'title' => 'Карточки', 'sources' => [TestEntry::$morphName], 'layout' => 'cards', 'sort' => 1]);
        $this->createBlock(['type' => Block::ENTRIES, 'title' => 'Плитка', 'sources' => [TestEntry::$morphName], 'layout' => 'gallery', 'sort' => 2]);

        $response = $this->get('/')->assertOk();

        $response->assertSeeInOrder(['Подробно', 'Запись без картинки', 'Тестовый раздел', $this->admin->login]);
        $response->assertSee('<div class="test-card">Карточка: Запись без картинки</div>', false);

        // Рамку рисует сам вид: у карточек рамки свои, вторая вокруг них не нужна
        $html = (string) $response->getContent();
        $this->assertSame(2, substr_count($html, '<div class="section shadow mb-3">'));

        // В плитку попадают только записи с картинкой
        $gallery = substr($html, (int) strpos($html, 'Плитка'));
        $this->assertStringNotContainsString('Запись без картинки', $gallery);
        $this->assertStringContainsString(__('home_builder::home_builder.gallery_empty'), $gallery);
    }

    public function testEntriesOfDisabledSourceAreSkipped(): void
    {
        $this->createBlock(['type' => Block::ENTRIES, 'title' => 'Выключенный раздел', 'sources' => ['missing'], 'layout' => 'list']);
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Текст на месте</p>', 'sort' => 1]);

        $this->get('/')
            ->assertOk()
            ->assertSee(__('main.empty_records'))
            ->assertSee('Текст на месте');
    }

    public function testEntriesCacheResetsOnNewRecord(): void
    {
        $this->createEntry('Первая запись', now()->subHour());
        $this->createBlock(['type' => Block::ENTRIES, 'sources' => [TestEntry::$morphName], 'layout' => 'list', 'cache_minutes' => 10]);

        $this->get('/')->assertSee('Первая запись');

        // Без новой версии ленты блок отдаёт кеш
        $this->createEntry('Мимо кеша', bumpVersion: false);
        $this->get('/')->assertDontSee('Мимо кеша');

        $this->createEntry('Вторая запись');
        $this->get('/')->assertSee('Вторая запись');
    }

    public function testShowDates(): void
    {
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Будущая акция</p>', 'starts_at' => now()->addDay()]);
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Прошедшая акция</p>', 'ends_at' => now()->subDay()]);
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Текущая акция</p>', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Текущая акция')
            ->assertDontSee('Будущая акция')
            ->assertDontSee('Прошедшая акция');
    }

    public function testGridWidth(): void
    {
        $this->createBlock(['type' => Block::TEXT, 'title' => 'Широкий', 'text' => '<p>Текст</p>', 'width' => 8]);
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Без заголовка</p>', 'width' => 4, 'sort' => 1]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['<div class="col-12 col-md-8">', 'Широкий', '<div class="col-12 col-md-4">', 'Без заголовка'], false)
            ->assertSee('<div class="section-title">Широкий</div>', false)
            ->assertDontSee('<div class="section-title"></div>', false);
    }

    public function testVisibilityForGuestsAndUsers(): void
    {
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Только гостям</p>', 'visibility' => Block::VISIBLE_GUESTS]);
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Только своим</p>', 'visibility' => Block::VISIBLE_USERS]);

        $this->get('/')
            ->assertSee('Только гостям')
            ->assertDontSee('Только своим');

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertSee('Только своим')
            ->assertDontSee('Только гостям');
    }

    public function testBossCreatesHtmlBlockOutputAsIs(): void
    {
        $code = '<div class="custom-banner" style="color: red">Баннер</div><script>window.homeBuilderTest = 1;</script>';

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=html', $this->form(['code' => $code]))
            ->assertRedirect(route('admin.home-builder.index'));

        $this->get('/')
            ->assertOk()
            ->assertSee($code, false);
    }

    public function testCreateWithArrayTypeIsNotFound(): void
    {
        // Массив вместо строки в адресе раньше ронял страницу с 500
        $this->actingAs($this->boss)->get('/admin/home-builder/create?type[]=html')->assertNotFound();
    }

    public function testHtmlBlockOfTagsOnlyIsAccepted(): void
    {
        // Длина кода считалась без тегов: виджет из одного iframe отклонялся как пустой
        $code = '<iframe src="https://example.com/widget" width="300" height="200"></iframe>';

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=html', $this->form(['code' => $code]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('home_blocks', ['type' => Block::HTML, 'code' => $code]);
    }

    public function testRightsAreCheckedBeforeFields(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/home-builder/create?type=html', $this->form(['code' => '']))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post('/admin/home-builder/create?type=feed', $this->form())
            ->assertNotFound();
    }

    public function testEditErrorReturnsToBlockForm(): void
    {
        $block = $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Привет</p>']);
        $form = route('admin.home-builder.edit', ['id' => $block->id]);

        $this->actingAs($this->admin)
            ->from($form)
            ->post('/admin/home-builder/' . $block->id . '/edit', $this->form(['text' => '<p><b></b></p>']))
            ->assertRedirect($form)
            ->assertSessionHasErrors('text');

        $this->assertSame('<p>Привет</p>', $block->fresh()->text);
    }

    public function testAdminCannotManageBossBlocks(): void
    {
        $html = $this->createBlock(['type' => Block::HTML, 'code' => '<b>Код</b>']);
        $entries = $this->createBlock(['type' => Block::ENTRIES, 'sources' => [TestEntry::$morphName], 'layout' => Block::LAYOUT_TEMPLATE, 'template' => 'test-any']);
        $this->createTemplate('test-any', 'Свой вид');

        $this->actingAs($this->admin)
            ->post('/admin/home-builder/create?type=html', $this->form(['code' => '<b>x</b>']))
            ->assertForbidden();

        $this->actingAs($this->admin)->get('/admin/home-builder/create?type=template')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/home-builder/' . $html->id . '/edit')->assertForbidden();
        $this->actingAs($this->admin)->delete('/admin/home-builder/' . $html->id)->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/home-builder/' . $entries->id . '/edit')->assertForbidden();

        // Свои виды админу не предлагаются и не принимаются, копию вида он не делает
        $this->actingAs($this->admin)
            ->get('/admin/home-builder/create?type=entries')
            ->assertOk()
            ->assertDontSee('custom:test-any', false);

        $this->actingAs($this->admin)
            ->post('/admin/home-builder/create?type=entries', $this->form(['sources' => [TestEntry::$morphName], 'layout' => 'custom:test-any']))
            ->assertSessionHasErrors('layout');

        $listBlock = $this->createBlock(['type' => Block::ENTRIES, 'sources' => [TestEntry::$morphName], 'layout' => 'list']);
        $this->actingAs($this->admin)->post('/admin/home-builder/' . $listBlock->id . '/customize')->assertForbidden();

        $this->actingAs($this->admin)
            ->get('/admin/home-builder')
            ->assertOk()
            ->assertDontSee('<option value="html">', false);
    }

    public function testTemplateBlockRendersCustomFile(): void
    {
        $this->createTemplate('test-banner', "@push('styles')<link id=\"template-style\">@endpush<div id=\"template-block\">{{ \$block->title }}</div>");

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=template', $this->form(['template' => 'test-banner', 'title' => 'Из шаблона']))
            ->assertRedirect(route('admin.home-builder.index'));

        $this->get('/')
            ->assertOk()
            ->assertSee('<div id="template-block">Из шаблона</div>', false)
            ->assertSee('<link id="template-style">', false);
    }

    public function testEntriesRenderInCustomLayout(): void
    {
        $this->createEntry('Запись в шаблоне');
        $this->createTemplate('test-entries', '@foreach ($entries as $entry)<b class="tpl-entry">{{ $entry->title }} — {{ $entry->section }}</b>@endforeach');

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=entries', $this->form([
                'sources' => [TestEntry::$morphName],
                'layout'  => 'custom:test-entries',
            ]))
            ->assertRedirect(route('admin.home-builder.index'));

        $block = Block::query()->sole();
        $this->assertSame([Block::LAYOUT_TEMPLATE, 'test-entries'], [$block->layout, $block->template]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<b class="tpl-entry">Запись в шаблоне — Тестовый раздел</b>', false);
    }

    public function testBossCustomizesBuiltInLayout(): void
    {
        $this->createEntry('Запись из копии');
        $block = $this->createBlock(['type' => Block::ENTRIES, 'title' => 'Копия', 'sources' => [TestEntry::$morphName], 'layout' => 'details']);

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/' . $block->id . '/customize')
            ->assertRedirect(route('admin.home-builder.edit', ['id' => $block->id]));

        $block->refresh();
        $file = Block::templatePath() . '/' . $block->template . '.blade.php';
        $this->templateFiles[] = $file;

        // Копия — тот же файл вида, блок показывает уже её
        $this->assertSame(Block::LAYOUT_TEMPLATE, $block->layout);
        $this->assertFileEquals(base_path('modules/HomeBuilder/resources/views/layouts/details.blade.php'), $file);

        file_put_contents($file, '<i class="from-copy">{{ $entries->first()->title }}</i>', FILE_APPEND);

        $this->get('/')
            ->assertOk()
            ->assertSee('Запись из копии')
            ->assertSee('<i class="from-copy">Запись из копии</i>', false);
    }

    public function testBossCreatesTemplateWithExampleOnSave(): void
    {
        $this->templateFiles[] = Block::templatePath() . '/test-stub-block.blade.php';

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=template', $this->form(['title' => 'Блок из заготовки', 'template_name' => 'test-stub-block.blade.php']))
            ->assertRedirect(route('admin.home-builder.edit', ['id' => Block::query()->max('id')]));

        $this->assertSame('test-stub-block', Block::query()->sole()->template);

        // Заготовка — рабочий шаблон: блок с ней выводится на главной
        $this->get('/')
            ->assertOk()
            ->assertSee('<div class="section-title">Блок из заготовки</div>', false)
            ->assertSee('Здесь будет свой блок');
    }

    public function testTemplateNameIsValidated(): void
    {
        $this->createTemplate('test-taken', 'Занято');

        foreach (['../evil', 'Promo', 'test-taken'] as $name) {
            $this->actingAs($this->boss)
                ->post('/admin/home-builder/create?type=template', $this->form(['template_name' => $name]))
                ->assertSessionHasErrors('template_name');
        }

        $this->assertSame('Занято', file_get_contents(Block::templatePath() . '/test-taken.blade.php'));
        $this->assertDatabaseCount('home_blocks', 0);
    }

    public function testShowDatesUseAdminTimezone(): void
    {
        $this->boss->update(['timezone' => '3']);

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=text', $this->form(['text' => '<p>Акция</p>', 'starts_at' => '2026-10-01T10:00']))
            ->assertSessionHasNoErrors();

        $block = Block::query()->sole();
        $this->assertSame('2026-10-01 07:00', $block->starts_at?->format('Y-m-d H:i'));

        // В форме — то же время, что ввёл админ
        $this->actingAs($this->boss)
            ->get('/admin/home-builder/' . $block->id . '/edit')
            ->assertSee('value="2026-10-01T10:00"', false);
    }

    public function testBrokenTemplateDoesNotBreakHomepage(): void
    {
        $this->createTemplate('test-broken', "@push('styles')<link id=\"broken-style\">@endpush{{ \$undefined->title }}");
        $this->createBlock(['type' => Block::TEMPLATE, 'title' => 'Сломанный', 'template' => 'test-broken']);
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Текст на месте</p>', 'sort' => 1]);

        // Гость: блок молча пропущен, остальная главная на месте
        $this->get('/')
            ->assertOk()
            ->assertSee('Текст на месте')
            ->assertDontSee('Сломанный');

        // Владелец видит, какой шаблон сломан
        $this->actingAs($this->boss)
            ->get('/')
            ->assertOk()
            ->assertSee('Текст на месте')
            ->assertSee('Undefined variable $undefined');
    }

    public function testTemplateMustExist(): void
    {
        // Форма шлёт пустой template_name всегда — раньше из-за него шаблон не проверялся
        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=template', $this->form(['template' => '../../views/index', 'template_name' => '']))
            ->assertSessionHasErrors('template');

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=template', $this->form(['template' => '', 'template_name' => '']))
            ->assertSessionHasErrors('template');

        $this->assertDatabaseCount('home_blocks', 0);
    }

    public function testSourcesNotArrayIsFormError(): void
    {
        // Раньше строка вместо списка и вложенный массив роняли проверку с 500
        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=entries', $this->form(['sources' => 'blogs']))
            ->assertSessionHasErrors('sources');

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=entries', $this->form(['sources' => [[TestEntry::$morphName]]]))
            ->assertSessionHasErrors('sources');

        $this->assertDatabaseCount('home_blocks', 0);
    }

    public function testSourcesAreStoredAsList(): void
    {
        // Ключи из запроса раньше попадали в JSON, и список сохранялся объектом {"5": ...}
        $this->actingAs($this->boss)
            ->post('/admin/home-builder/create?type=entries', $this->form(['sources' => [5 => TestEntry::$morphName]]))
            ->assertSessionHasNoErrors();

        $this->assertSame([TestEntry::$morphName], Block::query()->firstOrFail()->sources);
    }

    public function testBlockWithDeletedTemplateIsSkipped(): void
    {
        $this->createBlock(['type' => Block::TEMPLATE, 'template' => 'deleted-template']);
        $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Текст на месте</p>', 'sort' => 1]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Текст на месте');
    }

    public function testAdminCreatesTextBlock(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/home-builder/create?type=text', $this->form(['title' => 'Приветствие', 'text' => '<p>Привет</p>']))
            ->assertRedirect(route('admin.home-builder.index'));

        $this->assertDatabaseHas('home_blocks', ['type' => Block::TEXT, 'title' => 'Приветствие', 'active' => true]);
    }

    public function testAdminCreatesEntriesBlock(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/home-builder/create?type=entries', $this->form([
                'sources'       => [TestEntry::$morphName, 'comments'],
                'layout'        => 'gallery',
                'items'         => 7,
                'cache_minutes' => 15,
                'starts_at'     => '2026-10-01T10:00',
                'ends_at'       => '2026-10-31T18:30',
            ]))
            ->assertRedirect(route('admin.home-builder.index'));

        $block = Block::query()->sole();
        $this->assertSame([TestEntry::$morphName, 'comments'], $block->sources);
        $this->assertSame('gallery', $block->layout);
        $this->assertSame(7, $block->items);
        $this->assertSame(15, $block->cache_minutes);
        $this->assertSame('2026-10-01 10:00', $block->starts_at?->format('Y-m-d H:i'));
        $this->assertSame('2026-10-31 18:30', $block->ends_at?->format('Y-m-d H:i'));
    }

    public function testInvalidValuesAreRejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/home-builder/create?type=entries', $this->form([
                'sources'       => ['missing'],
                'layout'        => 'table',
                'items'         => 0,
                'cache_minutes' => 5000,
                'width'         => 5,
                'visibility'    => 'nobody',
                'starts_at'     => 'завтра',
            ]))
            ->assertSessionHasErrors(['sources', 'layout', 'items', 'cache_minutes', 'width', 'visibility', 'starts_at']);

        $this->actingAs($this->admin)
            ->post('/admin/home-builder/create?type=entries', $this->form([
                'layout'    => 'list',
                'starts_at' => '2026-10-10T10:00',
                'ends_at'   => '2026-10-01T10:00',
            ]))
            ->assertSessionHasErrors(['sources', 'ends_at']);

        $this->assertDatabaseCount('home_blocks', 0);
    }

    public function testUnknownTypeReturns404(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/home-builder/create?type=feed')
            ->assertNotFound();
    }

    public function testAdminEditsBlock(): void
    {
        $block = $this->createBlock(['type' => Block::ENTRIES, 'sources' => [TestEntry::$morphName], 'layout' => 'list']);

        $this->actingAs($this->admin)
            ->post('/admin/home-builder/' . $block->id . '/edit', $this->form([
                'title'   => 'Свой заголовок',
                'sources' => ['comments'],
                'layout'  => 'details',
                'items'   => 10,
                'width'   => 4,
                'active'  => 0,
            ]))
            ->assertRedirect(route('admin.home-builder.index'));

        $block->refresh();
        $this->assertSame('Свой заголовок', $block->title);
        $this->assertSame(['comments'], $block->sources);
        $this->assertSame('details', $block->layout);
        $this->assertSame(10, $block->items);
        $this->assertSame(4, $block->width);
        $this->assertFalse($block->active);
    }

    public function testAdminSortsBlocks(): void
    {
        // Одинаковый sort: порядок задаёт id, перетаскивание пишет его целиком
        $first = $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Первый</p>']);
        $second = $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Второй</p>']);
        $third = $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Третий</p>']);

        $this->actingAs($this->admin)
            ->post('/admin/home-builder/sort', ['sort' => $third->id . ',' . $first->id . ',' . $second->id])
            ->assertJson(['success' => true]);

        $this->assertSame([$third->id, $first->id, $second->id], Block::query()->ordered()->pluck('id')->all());

        $this->actingAs($this->admin)
            ->get('/admin/home-builder')
            ->assertSee('data-sortable-url="' . route('admin.home-builder.sort') . '"', false)
            ->assertSee('data-key="' . $third->id . '"', false);
    }

    public function testSortRejectsIncompleteOrForeignList(): void
    {
        $first = $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Первый</p>', 'sort' => 0]);
        $second = $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Второй</p>', 'sort' => 1]);

        foreach ([(string) $second->id, $second->id . ',' . ($second->id + 100), $second->id . ',' . $second->id, ''] as $sort) {
            $this->actingAs($this->admin)
                ->post('/admin/home-builder/sort', ['sort' => $sort])
                ->assertJson(['success' => false, 'message' => __('home_builder::home_builder.sort_invalid')]);
        }

        $this->assertSame([$first->id, $second->id], Block::query()->ordered()->pluck('id')->all());

        $this->actingAs(User::factory()->create())
            ->post('/admin/home-builder/sort', ['sort' => $second->id . ',' . $first->id])
            ->assertForbidden();
    }

    public function testAdminDeletesBlock(): void
    {
        $block = $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Текст</p>']);

        $this->actingAs($this->admin)
            ->delete('/admin/home-builder/' . $block->id)
            ->assertRedirect(route('admin.home-builder.index'));

        $this->assertDatabaseMissing('home_blocks', ['id' => $block->id]);
    }

    public function testAdminPagesRender(): void
    {
        $block = $this->createBlock(['type' => Block::TEXT, 'text' => '<p>Текст</p>']);
        $this->createBlock(['type' => Block::ENTRIES, 'sources' => [TestEntry::$morphName, 'missing'], 'layout' => 'list', 'ends_at' => now()->subDay()]);

        $this->actingAs($this->boss)
            ->get('/admin/home-builder')
            ->assertOk()
            ->assertSee('Тестовый раздел')
            ->assertSee('text-decoration-line-through', false)
            ->assertSee(__('home_builder::home_builder.out_of_dates'))
            ->assertSee('<option value="entries">', false)
            ->assertSee('<option value="html">', false);

        foreach (['/' . $block->id . '/edit', '/create?type=text', '/create?type=entries', '/create?type=html', '/create?type=template'] as $path) {
            $this->actingAs($this->boss)->get('/admin/home-builder' . $path)->assertOk();
        }
    }

    public function testFieldsTableShowsSampleValues(): void
    {
        $this->createEntry('Пример для полей');
        $block = $this->createBlock(['type' => Block::ENTRIES, 'sources' => [TestEntry::$morphName], 'layout' => 'list']);

        $this->actingAs($this->boss)
            ->get('/admin/home-builder/' . $block->id . '/edit')
            ->assertOk()
            ->assertSeeInOrder(['$entry->title', 'Пример для полей', '$entry->section', 'Тестовый раздел'], false)
            ->assertSee(__('home_builder::home_builder.customize'));
    }

    public function testPresetFillsEmptyHomepage(): void
    {
        // Из разделов набора на сайте есть только комментарии: блоки остальных пропускаются
        $this->actingAs($this->admin)
            ->post('/admin/home-builder/preset', ['preset' => 'community'])
            ->assertRedirect(route('admin.home-builder.index'))
            ->assertSessionHas('success');

        $this->assertSame([Block::TEXT, Block::ENTRIES], Block::query()->ordered()->pluck('type')->all());
        $this->assertSame(['comments'], Block::query()->where('type', Block::ENTRIES)->sole()->sources);

        // На заполненную главную набор не добавляется
        $this->actingAs($this->admin)
            ->post('/admin/home-builder/preset', ['preset' => 'portal'])
            ->assertSessionHas('danger');

        $this->assertDatabaseCount('home_blocks', 2);

        $this->actingAs($this->admin)
            ->post('/admin/home-builder/preset', ['preset' => 'missing'])
            ->assertNotFound();
    }

    public function testBossActivatesHomepage(): void
    {
        DB::table('settings')->updateOrInsert(['name' => 'homepage'], ['value' => 'feed']);

        $this->actingAs($this->admin)
            ->post('/admin/home-builder/activate')
            ->assertForbidden();

        $this->actingAs($this->boss)
            ->post('/admin/home-builder/activate')
            ->assertRedirect(route('admin.home-builder.index'));

        $this->assertDatabaseHas('settings', ['name' => 'homepage', 'value' => 'home-builder']);
    }

    public function testUserCannotManageBlocks(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/home-builder')
            ->assertForbidden();
    }

    /**
     * Поля формы блока по умолчанию
     */
    private function form(array $fields = []): array
    {
        return $fields + [
            'title'         => '',
            'layout'        => 'list',
            'items'         => 5,
            'cache_minutes' => 0,
            'width'         => 12,
            'visibility'    => Block::VISIBLE_ALL,
            'active'        => 1,
        ];
    }

    private function createBlock(array $attributes): Block
    {
        return Block::factory()->create($attributes + ['title' => '', 'items' => 5, 'sort' => 0, 'active' => true]);
    }

    /**
     * Запись тестового раздела со строкой в ленте, как у модулей с FeedableTrait
     */
    private function createEntry(string $title, mixed $createdAt = null, bool $bumpVersion = true): TestEntry
    {
        $entry = TestEntry::query()->create([
            'relate_type' => 'test',
            'relate_id'   => 1,
            'text'        => $title,
            'user_id'     => $this->admin->id,
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
            'created_at'  => $createdAt ?? now(),
        ]);

        Feed::query()->insert([
            'relate_type' => TestEntry::$morphName,
            'relate_id'   => $entry->id,
            'created_at'  => $entry->getAttribute('created_at'),
        ]);

        if ($bumpVersion) {
            cache()->increment('feed_version');
        }

        return $entry;
    }

    private function createTemplate(string $name, string $content): void
    {
        if (! is_dir(Block::templatePath())) {
            mkdir(Block::templatePath(), 0755, true);
        }

        $file = Block::templatePath() . '/' . $name . '.blade.php';
        $this->templateFiles[] = $file;
        file_put_contents($file, $content);
    }
}

/**
 * Запись раздела ленты без модуля: строки таблицы comments, заголовок — текст
 *
 * @property int $id
 */
class TestEntry extends Model
{
    public static string $morphName = 'test-entries';

    public $timestamps = false;

    protected $table = 'comments';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected function title(): Attribute
    {
        return Attribute::get(fn () => $this->getAttribute('text'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getViewUrl(): string
    {
        return '/test-entries/' . $this->id;
    }
}
