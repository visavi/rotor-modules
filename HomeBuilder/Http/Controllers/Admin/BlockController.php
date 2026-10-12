<?php

declare(strict_types=1);

namespace Modules\HomeBuilder\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Setting;
use App\Models\User;
use App\Services\FeedService;
use App\Support\Validator;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
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
    public function create(Request $request, Validator $validator): View|RedirectResponse
    {
        $type = $request->string('type')->value();

        if (! isset(Block::types()[$type])) {
            abort(404, __('home_builder::home_builder.type_not_found'));
        }

        if (! Block::canManage($type)) {
            abort(403, __('home_builder::home_builder.boss_only'));
        }

        $block = new Block([
            'type'          => $type,
            'layout'        => $type === Block::ENTRIES ? 'list' : '',
            'items'         => 5,
            'cache_minutes' => 0,
            'width'         => 12,
            'visibility'    => Block::VISIBLE_ALL,
            'active'        => true,
        ]);

        if ($request->isMethod('post')) {
            if ($this->fill($block, $request, $validator)) {
                $block->sort = (int) Block::query()->max('sort') + 1;
                $block->save();

                return $this->saved($block, __('home_builder::home_builder.block_added'));
            }

            return redirect()->route('admin.home-builder.create', ['type' => $type])
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        return $this->form($block);
    }

    /**
     * Редактирование блока
     */
    public function edit(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $block = $this->findBlock($id);

        if ($request->isMethod('post')) {
            if ($this->fill($block, $request, $validator)) {
                $block->save();

                return $this->saved($block, __('home_builder::home_builder.block_saved'));
            }

            return redirect()->route('admin.home-builder.edit', ['id' => $block->id])
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        return $this->form($block);
    }

    /**
     * Своя копия встроенного вида записей
     *
     * Файл вида копируется в каталог своих шаблонов, блок переключается на копию,
     * и она сразу открывается в редакторе файлов
     */
    public function customize(int $id): RedirectResponse
    {
        $block = $this->findBlock($id);

        if (! isAdmin(User::BOSS)) {
            abort(403, __('home_builder::home_builder.boss_only'));
        }

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
        $name = $request->string('preset')->value();

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
        if (! isAdmin(User::BOSS)) {
            abort(403, __('home_builder::home_builder.boss_only'));
        }

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
        $ids = array_map(int(...), explode(',', $request->string('sort')->value()));
        $current = Block::query()->pluck('id')->all();

        if (count($ids) !== count($current) || array_diff($current, $ids)) {
            return response()->json(['success' => false, 'message' => __('home_builder::home_builder.sort_invalid')]);
        }

        // Id — целые из базы, проверенные выше, поэтому подставляются в SQL напрямую
        $cases = '';

        foreach ($ids as $index => $id) {
            $cases .= ' WHEN ' . $id . ' THEN ' . $index;
        }

        Block::query()->whereKey($ids)->update(['sort' => DB::raw('CASE id' . $cases . ' END')]);

        return response()->json(['success' => true]);
    }

    /**
     * Удаление блока
     */
    public function delete(int $id): RedirectResponse
    {
        $this->findBlock($id)->delete();

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
    private function form(Block $block): View
    {
        $fields = $block->type === Block::ENTRIES && isAdmin(User::BOSS) ? $this->sampleEntry($block) : null;

        return view('home_builder::admin/form', compact('block', 'fields'));
    }

    /**
     * Пример записи для таблицы полей: последняя запись разделов блока, у нового блока — любого раздела
     */
    private function sampleEntry(Block $block): ?Entry
    {
        $sources = $block->sources ?: array_keys(Block::sourceList());
        $post = (new FeedService())->getLatest($sources, 1)->first();

        return $post ? Entry::from($post) : null;
    }

    /**
     * Заполняет блок из формы, false — есть ошибки
     */
    private function fill(Block $block, Request $request, Validator $validator): bool
    {
        $title = $request->string('title')->value();
        $text = $request->string('text')->value();
        $code = $request->string('code')->value();
        $template = $request->string('template')->value();
        $newTemplate = preg_replace('/\.blade\.php$/i', '', trim($request->string('template_name')->value())) ?? '';
        $sources = array_values(array_filter((array) $request->input('sources'), 'is_string'));
        $layoutOption = $request->string('layout')->value();
        $items = int($request->input('items'));
        $cacheMinutes = int($request->input('cache_minutes'));
        $width = int($request->input('width'));
        $visibility = $request->string('visibility')->value();
        $startsAt = $this->parseDate($request->string('starts_at')->value(), 'starts_at', $validator);
        $endsAt = $this->parseDate($request->string('ends_at')->value(), 'ends_at', $validator);

        $validator
            ->length($title, 0, 100, ['title' => __('validator.text')], false)
            ->in($width, Block::WIDTHS, ['width' => __('home_builder::home_builder.width_invalid')])
            ->in($visibility, [Block::VISIBLE_ALL, Block::VISIBLE_GUESTS, Block::VISIBLE_USERS], ['visibility' => __('home_builder::home_builder.visibility_invalid')]);

        if ($startsAt && $endsAt && $endsAt->lte($startsAt)) {
            $validator->addError(['ends_at' => __('home_builder::home_builder.ends_at_invalid')]);
        }

        // Свой вид записей приходит как custom:имя — это файл из каталога шаблонов
        $layout = $layoutOption;
        $layoutTemplate = '';

        if (str_starts_with($layoutOption, Block::CUSTOM_PREFIX)) {
            $layout = Block::LAYOUT_TEMPLATE;
            $layoutTemplate = substr($layoutOption, strlen(Block::CUSTOM_PREFIX));
        }

        match ($block->type) {
            Block::TEXT    => $validator->length($text, 1, 10000, ['text' => __('validator.text')]),
            Block::HTML    => $validator->length($code, 1, 65000, ['code' => __('validator.text')]),
            Block::ENTRIES => $validator
                ->between(count($sources), 1, 50, ['sources' => __('home_builder::home_builder.sources_invalid')])
                ->empty(array_diff($sources, array_keys(Block::sourceList())), ['sources' => __('home_builder::home_builder.sources_invalid')])
                ->in($layoutOption, array_keys(Block::layoutOptions()), ['layout' => __('home_builder::home_builder.layout_invalid')])
                ->between($items, 1, 50, ['items' => __('home_builder::home_builder.items_invalid')])
                ->between($cacheMinutes, 0, 1440, ['cache_minutes' => __('home_builder::home_builder.cache_invalid')]),
            default => $validator,
        };

        // Блок «Шаблон»: выбранный файл либо новый с примером внутри
        if ($block->type === Block::TEMPLATE) {
            if ($newTemplate !== '') {
                $this->validateNewTemplate($newTemplate, $validator);
                $template = $newTemplate;
            } else {
                $validator->in($template, Block::templates(), ['template' => __('home_builder::home_builder.template_invalid')]);
            }
        }

        if (! $validator->isValid()) {
            return false;
        }

        if ($block->type === Block::TEMPLATE && $newTemplate !== '' && ! $this->createTemplate($newTemplate)) {
            $validator->addError(['template_name' => __('home_builder::home_builder.template_write_failed')]);

            return false;
        }

        $isEntries = $block->type === Block::ENTRIES;

        $block->fill([
            'title'    => $title,
            'text'     => $block->type === Block::TEXT ? $text : null,
            'code'     => $block->type === Block::HTML ? $code : null,
            'template' => match ($block->type) {
                Block::TEMPLATE => $template,
                Block::ENTRIES  => $layoutTemplate,
                default         => '',
            },
            'sources'       => $isEntries ? $sources : null,
            'layout'        => $isEntries ? $layout : '',
            'items'         => $isEntries ? $items : $block->items,
            'cache_minutes' => $isEntries ? $cacheMinutes : 0,
            'width'         => $width,
            'visibility'    => $visibility,
            'active'        => $request->boolean('active'),
            'starts_at'     => $startsAt,
            'ends_at'       => $endsAt,
        ]);

        return true;
    }

    /**
     * Имя нового шаблона: без расширения, файла ещё нет
     */
    private function validateNewTemplate(string $name, Validator $validator): void
    {
        $error = match (true) {
            ! preg_match('/^[a-z0-9][a-z0-9\-]{0,49}$/', $name)             => __('home_builder::home_builder.template_name_invalid'),
            file_exists(Block::templatePath() . '/' . $name . '.blade.php') => __('home_builder::home_builder.template_exists'),
            default                                                         => null,
        };

        if ($error) {
            $validator->addError(['template_name' => $error]);
        }
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

    /**
     * Дата из поля datetime-local в часовом поясе админа, пустое поле — без ограничения
     */
    private function parseDate(string $value, string $field, Validator $validator): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            $validator->addError([$field => __('home_builder::home_builder.date_invalid')]);

            return null;
        }

        // Админ вводит своё время, как видит его в списке блоков (dateFixed сдвигает на пояс из анкеты)
        $date = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $value, config('app.timezone')) ?: null;

        return $date?->subHours((int) getUser('timezone'));
    }

    /**
     * Блок по id или 404, блок владельца — 403
     */
    private function findBlock(int $id): Block
    {
        $block = Block::query()->find($id);

        if (! $block) {
            abort(404, __('home_builder::home_builder.block_not_found'));
        }

        if (! $block->isManageable()) {
            abort(403, __('home_builder::home_builder.boss_only'));
        }

        return $block;
    }
}
