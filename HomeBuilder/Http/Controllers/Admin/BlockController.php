<?php

declare(strict_types=1);

namespace Modules\HomeBuilder\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Setting;
use App\Models\User;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Modules\HomeBuilder\Http\Requests\BlockRequest;
use Modules\HomeBuilder\Models\Block;
use Modules\HomeBuilder\Support\Entry;
use Modules\HomeBuilder\Support\Presets;

class BlockController extends AdminController
{
    /**
     * Создан ли при сохранении блока новый шаблон: тогда после сохранения открывается правка блока
     */
    private bool $templateCreated = false;

    /**
     * Список блоков
     */
    public function index(): View
    {
        $blocks = Block::query()->ordered()->get();

        $types = array_filter(
            Block::types(),
            static fn (string $type) => Block::canManage($type),
            ARRAY_FILTER_USE_KEY,
        );

        return view('home_builder::admin/index', compact('blocks', 'types'));
    }

    /**
     * Добавление блока
     */
    public function create(Request $request, FeedService $feed): View
    {
        $type = $request->input('type');

        return $this->form(Block::draft(is_string($type) ? $type : ''), $feed);
    }

    /**
     * Сохранение нового блока
     */
    public function store(BlockRequest $request): RedirectResponse
    {
        $block = $request->block();

        if (! $this->fill($block, $request)) {
            return back()->withInput()->withErrors(['template_name' => __('home_builder::home_builder.template_write_failed')]);
        }

        $block->sort = (int) Block::query()->max('sort') + 1;
        $block->save();

        return $this->saved($block, __('home_builder::home_builder.block_added'));
    }

    /**
     * Редактирование блока
     */
    public function edit(int $id, FeedService $feed): View
    {
        return $this->form(Block::findManageable($id), $feed);
    }

    /**
     * Сохранение блока
     */
    public function update(BlockRequest $request): RedirectResponse
    {
        $block = $request->block();

        if (! $this->fill($block, $request)) {
            return back()->withInput()->withErrors(['template_name' => __('home_builder::home_builder.template_write_failed')]);
        }

        $block->save();

        return $this->saved($block, __('home_builder::home_builder.block_saved'));
    }

    /**
     * Своя копия встроенного вида записей
     *
     * Файл вида копируется в каталог своих шаблонов, блок переключается на копию,
     * и она сразу открывается в редакторе файлов
     */
    public function customize(int $id): RedirectResponse
    {
        $block = Block::findManageable($id);

        if ($block->type !== Block::ENTRIES || ! in_array($block->layout, Block::LAYOUTS, true)) {
            abort(404, __('home_builder::home_builder.layout_invalid'));
        }

        if (! is_dir(Block::templatePath())) {
            @mkdir(Block::templatePath(), 0755, true);
        }

        $name = $block->layout;
        for ($i = 2; file_exists(Block::templatePath() . '/' . $name . '.blade.php'); $i++) {
            $name = $block->layout . '-' . $i;
        }

        $original = dirname(__DIR__, 3) . '/resources/views/layouts/' . $block->layout . '.blade.php';

        if (! @copy($original, Block::templatePath() . '/' . $name . '.blade.php')) {
            return redirect()->route('admin.home-builder.edit', ['id' => $block->id])
                ->with('danger', __('home_builder::home_builder.template_write_failed'));
        }

        $block->update(['layout' => Block::LAYOUT_TEMPLATE, 'template' => $name]);

        $message = __('home_builder::home_builder.layout_customized', ['name' => $name]);

        if (Route::has('admin.files.edit')) {
            return redirect()->route('admin.files.edit', ['root' => 'custom', 'path' => 'views/' . Block::TEMPLATE_DIR, 'file' => $name . '.blade.php'])
                ->with('success', $message);
        }

        return redirect()->route('admin.home-builder.edit', ['id' => $block->id])->with('success', $message);
    }

    /**
     * Готовый набор блоков для пустой главной
     */
    public function preset(Request $request): RedirectResponse
    {
        $name = $request->input('preset');

        if (! in_array($name, Presets::NAMES, true)) {
            abort(404, __('home_builder::home_builder.preset_not_found'));
        }

        // Набор только для пустой главной: на заполненной он задвоил бы блоки
        if (Block::query()->exists()) {
            return redirect()->route('admin.home-builder.index')
                ->with('danger', __('home_builder::home_builder.preset_not_empty'));
        }

        foreach (Presets::blocks($name) as $sort => $attributes) {
            Block::query()->create($attributes + [
                'items'      => 5,
                'width'      => 12,
                'visibility' => Block::VISIBLE_ALL,
                'active'     => true,
                'sort'       => $sort,
            ]);
        }

        return redirect()->route('admin.home-builder.index')
            ->with('success', __('home_builder::home_builder.preset_created'));
    }

    /**
     * Делает блоки конструктора главной сайта
     */
    public function activate(): RedirectResponse
    {
        Setting::query()->where('name', 'homepage')->update(['value' => 'home-builder']);
        clearCache('settings');

        return redirect()->route('admin.home-builder.index')
            ->with('success', __('home_builder::home_builder.activated'));
    }

    /**
     * Порядок блоков после перетаскивания в списке
     *
     * Приходит весь список id: порядок пишется целиком, иначе одинаковые sort
     * у блоков путали бы его
     */
    public function sort(Request $request): JsonResponse
    {
        $sort = $request->input('sort');
        $ids = array_map(intval(...), explode(',', is_string($sort) ? $sort : ''));
        $current = Block::query()->pluck('id')->all();

        if (count($ids) !== count($current) || array_diff($current, $ids)) {
            return response()->json(['success' => false, 'message' => __('home_builder::home_builder.sort_invalid')], 422);
        }

        // Id — целые из базы, проверенные выше, поэтому подставляются в SQL напрямую
        $cases = '';

        foreach ($ids as $index => $id) {
            $cases .= ' WHEN ' . $id . ' THEN ' . $index;
        }

        Block::query()->whereKey($ids)->update(['sort' => DB::raw('CASE id' . $cases . ' END')]); // @phpstan-ignore argument.type (в SQL только целые, собранные выше)

        return response()->json(['success' => true]);
    }

    /**
     * Удаление блока
     */
    public function delete(int $id): RedirectResponse
    {
        Block::findManageable($id)->delete();

        return redirect()->route('admin.home-builder.index')
            ->with('success', __('home_builder::home_builder.block_deleted'));
    }

    /**
     * Ответ после сохранения: с новым шаблоном — в правку блока, где есть ссылка на шаблон
     */
    private function saved(Block $block, string $message): RedirectResponse
    {
        if ($this->templateCreated) {
            return redirect()->route('admin.home-builder.edit', ['id' => $block->id])
                ->with('success', __('home_builder::home_builder.template_created'));
        }

        return redirect()->route('admin.home-builder.index')->with('success', $message);
    }

    /**
     * Форма блока
     */
    private function form(Block $block, FeedService $feed): View
    {
        $fields = $block->type === Block::ENTRIES && isAdmin(User::BOSS) ? $this->sampleEntry($block, $feed) : null;

        return view('home_builder::admin/form', compact('block', 'fields'));
    }

    /**
     * Пример записи для таблицы полей: последняя запись разделов блока, у нового блока — любого раздела
     */
    private function sampleEntry(Block $block, FeedService $feed): ?Entry
    {
        $sources = $block->sources ?: array_keys(Block::sourceList());
        $post = $feed->getLatest($sources, 1)->first();

        return $post ? Entry::from($post) : null;
    }

    /**
     * Заполняет блок из формы, false — новый шаблон не записался
     */
    private function fill(Block $block, BlockRequest $request): bool
    {
        $newTemplate = $request->newTemplate();

        if ($newTemplate !== null && ! $this->createTemplate($newTemplate)) {
            return false;
        }

        $block->fill($request->blockFields());

        return true;
    }

    /**
     * Создаёт шаблон блока с примером внутри
     */
    private function createTemplate(string $name): bool
    {
        if (! is_dir(Block::templatePath())) {
            @mkdir(Block::templatePath(), 0755, true);
        }

        $stub = dirname(__DIR__, 3) . '/resources/stubs/template.blade.stub';

        $this->templateCreated = @file_put_contents(Block::templatePath() . '/' . $name . '.blade.php', (string) file_get_contents($stub)) !== false;

        return $this->templateCreated;
    }
}
