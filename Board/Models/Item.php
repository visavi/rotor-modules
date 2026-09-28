<?php

declare(strict_types=1);

namespace Modules\Board\Models;

use App\Casts\HtmlCast;
use App\Casts\TextCast;
use App\Models\File;
use App\Models\User;
use App\Traits\ConvertVideoTrait;
use App\Traits\FeedableTrait;
use App\Traits\FileableTrait;
use App\Traits\SearchableTrait;
use App\Traits\SortableTrait;
use App\Traits\UploadTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Modules\Board\Casts\CityCast;

/**
 * Class Item
 *
 * @property int             $id
 * @property int             $board_id
 * @property string          $title
 * @property string          $text
 * @property int             $user_id
 * @property int             $price
 * @property string          $phone
 * @property string          $city
 * @property list<string>    $messengers
 * @property bool            $active
 * @property int             $visits
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable $expires_at
 * @property-read User                  $user
 * @property-read Board                 $category
 * @property-read Collection<int, File> $files
 */
class Item extends Model
{
    use ConvertVideoTrait;
    use FeedableTrait;
    use FileableTrait;
    use SearchableTrait;
    use SortableTrait;
    use UploadTrait;

    /**
     * Мессенджеры, в которых автор может отметить свой номер
     *
     * url — чат по номеру, {phone} подставляется без плюса. У MAX ссылки
     * по номеру нет, поэтому он только отмечается. Без icon название
     * выводится плашкой: в Font Awesome есть не все значки
     *
     * @var array<string, array{label: string, icon: ?string, color: string, url: ?string}>
     */
    public const MESSENGERS = [
        'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'fa-brands fa-whatsapp', 'color' => '#25d366', 'url' => 'https://wa.me/{phone}'],
        'telegram' => ['label' => 'Telegram', 'icon' => 'fa-brands fa-telegram', 'color' => '#29a9eb', 'url' => 'https://t.me/+{phone}'],
        'viber'    => ['label' => 'Viber', 'icon' => 'fa-brands fa-viber', 'color' => '#7360f2', 'url' => 'viber://chat?number=%2B{phone}'],
        'max'      => ['label' => 'MAX', 'icon' => null, 'color' => '#6f4bff', 'url' => null],
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Директория загрузки файлов
     */
    public string $uploadPath = '/uploads/boards';

    /**
     * Morph name
     */
    public static string $morphName = 'items';

    /**
     * Counting field
     */
    public string $countingField = 'visits';

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'title'      => TextCast::class,
            'city'       => CityCast::class,
            'active'     => 'bool',
            'user_id'    => 'int',
            'text'       => HtmlCast::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Мессенджеры без номера бессмысленны: стёр телефон — снялись и отметки
     */
    protected static function booted(): void
    {
        static::saving(static function (self $item) {
            if (blank($item->phone)) {
                $item->messengers = [];
            }
        });
    }

    /**
     * Отметки мессенджеров: в базе строка ключей через запятую
     *
     * Неизвестные ключи отбрасываются, порядок — как в MESSENGERS
     */
    protected function messengers(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): array => $value ? explode(',', $value) : [],
            set: static fn (mixed $value): string => implode(',', array_intersect(
                array_keys(self::MESSENGERS),
                array_filter((array) $value, 'is_string'),
            )),
        );
    }

    /**
     * Мессенджеры, включённые в настройках модуля
     *
     * Настройки ещё нет — мессенджер включён: новый пункт MESSENGERS
     * работает без миграции настроек
     *
     * @return array<string, array{label: string, icon: ?string, color: string, url: ?string}>
     */
    public static function enabledMessengers(): array
    {
        return array_filter(
            self::MESSENGERS,
            static fn (string $key): bool => (bool) (setting('board_messenger_' . $key) ?? true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Отмеченные автором и включённые на сайте, со ссылкой на чат по номеру
     *
     * @return array<string, array{label: string, icon: ?string, color: string, url: ?string}>
     */
    public function getMessengers(): array
    {
        $phone = ltrim((string) $this->phone, '+');

        return array_map(
            static fn (array $messenger): array => ['url' => $messenger['url'] ? str_replace('{phone}', $phone, $messenger['url']) : null] + $messenger,
            array_intersect_key(self::enabledMessengers(), array_flip($this->messengers)),
        );
    }

    /**
     * Возвращает поля участвующие в поиске
     */
    public function searchableFields(): array
    {
        return ['title', 'text'];
    }

    /**
     * Возвращает список сортируемых полей
     */
    protected static function sortableFields(): array
    {
        return [
            'date'  => ['field' => 'updated_at', 'label' => __('main.date')],
            'price' => ['field' => 'price', 'label' => __('main.cost')],
            'name'  => ['field' => 'title', 'label' => __('main.title')],
        ];
    }

    /**
     * Scope a query to only include active records.
     */
    #[Scope]
    protected function active(Builder $query, bool $active = true): void
    {
        $query->where('active', $active);
    }

    /**
     * Возвращает связь пользователя
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /**
     * Возвращает категорию объявлений
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id')->withDefault();
    }

    /**
     * Возвращает путь к первому файлу
     */
    public function getFirstImage(): HtmlString
    {
        $image = $this->files->first();

        $path = $image->path ?? null;

        if ($path) {
            return new HtmlString('<img src="' . e($path) . '" alt="' . e($this->title) . '" class="img-fluid">');
        }

        return new HtmlString('<div class="text-center text-secondary py-3"><i class="fa fa-image fa-5x"></i></div>');
    }

    /**
     * Цена с валютой сайта, разряды через неразрывный пробел: 12 500 руб
     */
    public function getPrice(): string
    {
        return number_format($this->price, 0, '', "\u{00A0}") . "\u{00A0}" . setting('currency');
    }

    /**
     * Get text
     */
    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'item-' . $this->id);
    }

    /**
     * Ссылка на страницу записи
     */
    public function getViewUrl(bool $absolute = true): string
    {
        return route('items.view', ['id' => $this->id], $absolute);
    }

    /**
     * Путь до раздела записи
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function getBreadcrumbs(bool $absolute = true): array
    {
        $breadcrumbs = [
            ['title' => __('board::boards.boards'), 'url' => route('boards.index', [], $absolute)],
        ];

        if ($this->category->parent->id) {
            $breadcrumbs[] = [
                'title' => $this->category->parent->name,
                'url'   => route('boards.index', ['id' => $this->category->parent->id], $absolute),
            ];
        }

        if ($this->category->id) {
            $breadcrumbs[] = [
                'title' => $this->category->name,
                'url'   => route('boards.index', ['id' => $this->category->id], $absolute),
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Удаление объявления и загруженных файлов
     */
    public function delete(): ?bool
    {
        return DB::transaction(function () {
            $this->files->each(static function (File $file) {
                $file->delete();
            });

            return parent::delete();
        });
    }
}
