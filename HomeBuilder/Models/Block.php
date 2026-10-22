<?php

declare(strict_types=1);

namespace Modules\HomeBuilder\Models;

use App\Casts\HtmlCast;
use App\Casts\TextCast;
use App\Models\User;
use App\Services\FeedService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Modules\HomeBuilder\Support\Entry;
use Throwable;

/**
 * Блок своей главной
 *
 * Тип — текст, записи разделов из ленты, HTML-код или шаблон. Оформление записей
 * задаёт вид: встроенный шаблон модуля либо своя копия в resources/custom/views
 *
 * @property int                  $id
 * @property string               $type
 * @property string               $title
 * @property string               $text
 * @property string|null          $code
 * @property string               $template
 * @property array|null           $sources
 * @property string               $layout
 * @property int                  $items
 * @property int                  $cache_minutes
 * @property int                  $width
 * @property string               $visibility
 * @property int                  $sort
 * @property bool                 $active
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 */
class Block extends Model
{
    /** @use HasFactory<\Modules\HomeBuilder\Tests\Factories\BlockFactory> */
    use HasFactory;

    public const string TEXT = 'text';
    public const string HTML = 'html';
    public const string TEMPLATE = 'template';
    public const string ENTRIES = 'entries';

    /**
     * Встроенные виды записей — шаблоны модуля в resources/views/layouts
     */
    public const array LAYOUTS = ['list', 'details', 'cards', 'gallery'];

    /**
     * Свой вид: копия встроенного или свой файл из каталога шаблонов
     */
    public const string LAYOUT_TEMPLATE = 'template';

    /**
     * Приставка своего вида в списке видов формы
     */
    public const string CUSTOM_PREFIX = 'custom:';

    public const string VISIBLE_ALL = 'all';
    public const string VISIBLE_GUESTS = 'guests';
    public const string VISIBLE_USERS = 'users';

    /**
     * Ширина блока в колонках сетки Bootstrap
     */
    public const array WIDTHS = [12, 8, 6, 4, 3];

    /**
     * Каталог своих шаблонов внутри resources/custom/views
     */
    public const string TEMPLATE_DIR = 'home-builder';

    protected $table = 'home_blocks';

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'title'         => TextCast::class,
            'text'          => HtmlCast::class,
            'sources'       => 'array',
            'items'         => 'int',
            'cache_minutes' => 'int',
            'width'         => 'int',
            'sort'          => 'int',
            'active'        => 'bool',
            'starts_at'     => 'datetime',
            'ends_at'       => 'datetime',
        ];
    }

    /**
     * Блоки в порядке вывода
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('id');
    }

    /**
     * Блоки, которые видит текущий посетитель
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('active', true)
            ->whereIn('visibility', [self::VISIBLE_ALL, getUser() ? self::VISIBLE_USERS : self::VISIBLE_GUESTS])
            ->where(static fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(static fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /**
     * Типы блоков
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TEXT     => __('home_builder::home_builder.type_text'),
            self::ENTRIES  => __('home_builder::home_builder.type_entries'),
            self::HTML     => __('home_builder::home_builder.type_html'),
            self::TEMPLATE => __('home_builder::home_builder.type_template'),
        ];
    }

    /**
     * Разделы, из которых блок берёт записи: всё, что модули подключили к ленте
     *
     * @return array<string, string>
     */
    public static function sourceList(): array
    {
        $sources = [];

        foreach (array_keys(FeedService::allTypes()) as $type) {
            $sources[$type] = Entry::sectionLabel($type);
        }

        return $sources;
    }

    /**
     * Записи блока: свежие записи выбранных разделов
     *
     * В кеше лежат модели, а не готовые записи: кеш сбрасывается и новой
     * записью в ленте, и правкой блока
     *
     * @return Collection<int, Entry>
     */
    public function entries(): Collection
    {
        $sources = $this->sources ?? [];
        $fetch = fn () => (new FeedService())->getLatest($sources, $this->items);

        if ($this->cache_minutes < 1) {
            $models = $fetch();
        } else {
            $version = cache()->get('feed_version', 0);
            $key = "home_block_{$this->id}_{$this->updated_at->timestamp}_{$version}";

            $models = cache()->remember($key, $this->cache_minutes * 60, $fetch);
        }

        return $models->map(Entry::from(...));
    }

    /**
     * Типы, которые правит только владелец: HTML и шаблон выводятся без чистки
     */
    public static function isBossType(string $type): bool
    {
        return in_array($type, [self::HTML, self::TEMPLATE], true);
    }

    /**
     * Может ли текущий админ добавлять блоки этого типа
     */
    public static function canManage(string $type): bool
    {
        return ! self::isBossType($type) || isAdmin(User::BOSS);
    }

    /**
     * Может ли текущий админ править этот блок: записи своим видом — тоже только владелец
     */
    public function isManageable(): bool
    {
        return self::canManage($this->type)
            && ($this->layout !== self::LAYOUT_TEMPLATE || isAdmin(User::BOSS));
    }

    /**
     * Блок по id или 404, блок владельца — 403
     */
    public static function findManageable(int $id): self
    {
        $block = self::query()->find($id);

        if (! $block) {
            abort(404, __('home_builder::home_builder.block_not_found'));
        }

        if (! $block->isManageable()) {
            abort(403, __('home_builder::home_builder.boss_only'));
        }

        return $block;
    }

    /**
     * Новый блок типа с настройками по умолчанию: неизвестный тип — 404, тип владельца — 403
     */
    public static function draft(string $type): self
    {
        if (! isset(self::types()[$type])) {
            abort(404, __('home_builder::home_builder.type_not_found'));
        }

        if (! self::canManage($type)) {
            abort(403, __('home_builder::home_builder.boss_only'));
        }

        return new self([
            'type'          => $type,
            'layout'        => $type === self::ENTRIES ? 'list' : '',
            'items'         => 5,
            'cache_minutes' => 0,
            'width'         => 12,
            'visibility'    => self::VISIBLE_ALL,
            'active'        => true,
        ]);
    }

    /**
     * Виды записей для формы: встроенные и, владельцу, свои шаблоны
     *
     * @return array<string, string>
     */
    public static function layoutOptions(): array
    {
        $options = [];

        foreach (self::LAYOUTS as $layout) {
            $options[$layout] = __('home_builder::home_builder.layout_' . $layout);
        }

        if (isAdmin(User::BOSS)) {
            foreach (self::templates() as $template) {
                $options[self::CUSTOM_PREFIX . $template] = __('home_builder::home_builder.layout_custom', ['name' => $template]);
            }
        }

        return $options;
    }

    /**
     * Значение вида блока в списке видов формы
     */
    public function layoutOption(): string
    {
        return $this->layout === self::LAYOUT_TEMPLATE ? self::CUSTOM_PREFIX . $this->template : $this->layout;
    }

    /**
     * Шаблон вида записей, null — свой файл удалён
     */
    public function layoutView(): ?string
    {
        if ($this->layout === self::LAYOUT_TEMPLATE) {
            return $this->templateView();
        }

        return in_array($this->layout, self::LAYOUTS, true) ? 'home_builder::layouts.' . $this->layout : null;
    }

    /**
     * Рендер шаблона блока с изоляцией ошибок
     *
     * Ошибка в своём шаблоне не роняет главную: блок пропускается, ошибка уходит
     * в лог, владелец видит её на месте блока. Шаблон рендерится движком напрямую:
     * View::render() при ошибке сбрасывает секции и стеки всей страницы
     */
    public function renderView(string $view, array $data): HtmlString
    {
        $factory = app('view');

        try {
            $path = $factory->getFinder()->find($view);

            return new HtmlString($factory->getEngineFromPath($path)->get($path, array_merge($factory->getShared(), $data)));
        } catch (Throwable $e) {
            report($e);

            if (! isAdmin(User::BOSS)) {
                return new HtmlString('');
            }

            return new HtmlString((string) view('home_builder::_error', ['block' => $this, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Свои шаблоны из resources/custom/views/home-builder
     *
     * @return list<string>
     */
    public static function templates(): array
    {
        $files = glob(self::templatePath() . '/*.blade.php') ?: [];

        return array_map(static fn (string $file) => basename($file, '.blade.php'), $files);
    }

    /**
     * Полный путь к каталогу своих шаблонов
     */
    public static function templatePath(): string
    {
        return resource_path('custom/views/' . self::TEMPLATE_DIR);
    }

    /**
     * Имя вью своего шаблона, null — файла нет
     */
    public function templateView(): ?string
    {
        $view = self::TEMPLATE_DIR . '/' . $this->template;

        return $this->template !== '' && View::exists($view) ? $view : null;
    }

    /**
     * Название типа блока
     */
    public function getTypeLabel(): string
    {
        return self::types()[$this->type] ?? $this->type;
    }

    /**
     * Нужен ли блоку свой файл шаблона: тип «Шаблон» или записи своим видом
     */
    public function usesTemplate(): bool
    {
        return $this->type === self::TEMPLATE
            || ($this->type === self::ENTRIES && $this->layout === self::LAYOUT_TEMPLATE);
    }

    /**
     * Показывается ли блок сейчас: включён и попадает в даты показа
     */
    public function isShownNow(): bool
    {
        return $this->active
            && (! $this->starts_at || $this->starts_at->lte(now()))
            && (! $this->ends_at || $this->ends_at->gt(now()));
    }

    /**
     * Классы колонки сетки: на телефоне блок всегда во всю ширину
     */
    public function columnClass(): string
    {
        return $this->width === 12 ? 'col-12' : 'col-12 col-md-' . $this->width;
    }
}
