<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Class PaidAdvert
 *
 * @property int             $id
 * @property string          $place
 * @property string          $url
 * @property array           $names
 * @property string|null     $text
 * @property string|null     $image
 * @property string|null     $comment
 * @property string          $color
 * @property bool            $bold
 * @property int|null        $user_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $deleted_at
 */
class PaidAdvert extends Model
{
    /** @use HasFactory<\Modules\Payment\Tests\Factories\PaidAdvertFactory> */
    use HasFactory;

    /**
     * Места размещения (идентификаторы)
     */
    public const string TOP_ALL = 'top_all';
    public const string TOP = 'top';
    public const string FORUM = 'forum';
    public const string BOTTOM_ALL = 'bottom_all';
    public const string BOTTOM = 'bottom';

    /**
     * Места размещения
     */
    public const array PLACES = [
        self::TOP_ALL,
        self::TOP,
        self::FORUM,
        self::BOTTOM_ALL,
        self::BOTTOM,
    ];

    /**
     * Места рекламных карточек: продаются отдельно от ссылок, в PLACES не входят
     */
    public const string CARD_SIDEBAR = 'card_sidebar';
    public const string CARD_FEED_START = 'card_feed_start';
    public const string CARD_FEED_MIDDLE = 'card_feed_middle';
    public const string CARD_FEED_END = 'card_feed_end';

    public const array CARD_PLACES = [
        self::CARD_SIDEBAR,
        self::CARD_FEED_START,
        self::CARD_FEED_MIDDLE,
        self::CARD_FEED_END,
    ];

    /**
     * Места в ленте по точкам, где ядро вызывает хук feedAdvert: в начале, в середине и в конце первой порции
     */
    public const array CARD_FEED_POINTS = [
        'start'  => self::CARD_FEED_START,
        'middle' => self::CARD_FEED_MIDDLE,
        'end'    => self::CARD_FEED_END,
    ];

    /**
     * Директория загрузки картинок карточек
     */
    public string $uploadPath = '/uploads/paid-adverts';

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'bold'       => 'bool',
            'names'      => 'array',
            'user_id'    => 'int',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The name of the "updated at" column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Возвращает связь пользователя
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /**
     * Карточка (а не ссылка)
     */
    public function isCard(): bool
    {
        return in_array($this->place, self::CARD_PLACES, true);
    }

    /**
     * Реклама, срок которой не истёк
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('deleted_at', '>', now());
    }

    /**
     * Реклама текущего пользователя
     *
     * whereNotNull обязателен: у гостя auth()->id() — null, и where превратился бы
     * в whereNull, отдав рекламу, купленную гостями
     */
    public function scopeOwn(Builder $query): Builder
    {
        return $query
            ->whereNotNull('user_id')
            ->where('user_id', auth()->id());
    }

    /**
     * Активная реклама текущего пользователя
     */
    public function scopeOwnActive(Builder $query): Builder
    {
        return $this->scopeActive($this->scopeOwn($query));
    }

    /**
     * Срок рекламы не истёк
     */
    public function isActive(): bool
    {
        return $this->deleted_at->isFuture();
    }

    /**
     * Адрес продления рекламы владельцем
     */
    public function renewUrl(): string
    {
        return ($this->isCard() ? '/payments/my/card/renew/' : '/payments/my/renew/') . $this->id;
    }

    /**
     * Get places
     */
    public function getPlaces(): array
    {
        $places = [];
        foreach (self::PLACES as $place) {
            $places[$place] = __('payment::payments.paid_adverts.' . $place);
        }

        return $places;
    }

    /**
     * Возвращает название места размещения
     */
    public function getPlaceName(): string
    {
        if ($this->isCard()) {
            return __('payment::payments.cards.' . $this->place);
        }

        return $this->getPlaces()[$this->place] ?? 'Unknown';
    }

    /**
     * Данные карточки для вывода
     *
     * @return array{title: string, text: ?string, url: string, image: ?string, expires: int}
     */
    public function cardData(): array
    {
        return [
            'title'   => $this->names[0] ?? '',
            'text'    => $this->text,
            'url'     => $this->url,
            'image'   => $this->image,
            'expires' => $this->deleted_at->getTimestamp(),
        ];
    }

    /**
     * Текст карточки для вывода: экранирован, переносы строк сохранены
     *
     * Пустые строки выкидываются, строк не больше трёх (остальное склеивается
     * в третью): иначе в 200 символов влезают десятки переносов и карточка
     * вытягивается в столб
     */
    public static function cardTextHtml(?string $text, ?int $limit = null): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $text))));

        if (count($lines) > 3) {
            $lines = [$lines[0], $lines[1], implode(' ', array_slice($lines, 2))];
        }

        $text = implode("\n", $lines);

        if ($limit) {
            $text = Str::limit($text, $limit);
        }

        return nl2br(e($text));
    }

    /**
     * Кеширует активные карточки по местам
     *
     * @return array<string, array<int, array{title: string, text: ?string, url: string, image: ?string, expires: int}>>
     */
    public static function activeCards(): array
    {
        return Cache::remember('paidCards', 3600, static function () {
            $cards = [];

            $data = self::query()
                ->active()
                ->whereIn('place', self::CARD_PLACES)
                ->get();

            foreach ($data as $card) {
                $cards[$card->place][] = $card->cardData();
            }

            return $cards;
        });
    }

    /**
     * Случайная активная карточка места: карточки показываются по очереди
     *
     * Срок проверяется и здесь: кэш живёт час, а истёкшая карточка
     * не должна делить место с купленной на освободившееся
     *
     * @return array{title: string, text: ?string, url: string, image: ?string, expires: int}|null
     */
    public static function randomCard(string $place): ?array
    {
        $now = now()->getTimestamp();
        $cards = array_filter(self::activeCards()[$place] ?? [], static fn (array $card) => $card['expires'] > $now);

        return $cards ? Arr::random($cards) : null;
    }

    /**
     * Кеширует ссылки платной рекламы
     */
    public static function statAdverts(): array
    {
        return Cache::remember('paidAdverts', 3600, static function () {
            $data = self::query()
                ->where('deleted_at', '>', now())
                ->whereNotIn('place', self::CARD_PLACES)
                ->orderBy('created_at')
                ->get();

            $links = [];
            if ($data->isNotEmpty()) {
                foreach ($data as $val) {
                    $names = check($val->names);

                    $sites = [];
                    foreach ($names as $name) {
                        if ($val->color) {
                            $name = '<span style="color:' . $val->color . '">' . $name . '</span>';
                        }

                        $link = '<a href="' . $val->url . '" target="_blank">' . $name . '</a>';

                        if ($val->bold) {
                            $link = '<b>' . $link . '</b>';
                        }

                        $sites[] = $link;
                    }

                    $links[$val->place][] = $sites;
                }
            }

            return $links;
        });
    }

    /**
     * Возвращает HTML-блок рекламы для указанного места
     */
    public static function renderAdvert(string $place): ?HtmlString
    {
        $adverts = self::statAdverts();

        if (isset($adverts[$place])) {
            $links = [];
            foreach ($adverts[$place] as $advert) {
                $links[] = Arr::random($advert);
            }

            return new HtmlString(implode('<br>', $links));
        }

        return null;
    }
}
