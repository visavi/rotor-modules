<?php

declare(strict_types=1);

namespace Modules\UserField\Models;

use App\Casts\TextCast;
use App\Support\HtmlSanitizer;
use App\Support\Validator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class UserField
 *
 * @property int         $id
 * @property int         $sort
 * @property string      $type
 * @property string      $name
 * @property string      $placeholder
 * @property string      $hint
 * @property string|null $options
 * @property int         $min
 * @property int         $max
 * @property bool        $required
 * @property-read Collection<UserData> $data
 */
class UserField extends Model
{
    /**
     * Type fields
     */
    public const string INPUT = 'input';
    public const string TEXTAREA = 'textarea';
    public const string URL = 'url';
    public const string EMAIL = 'email';
    public const string TEL = 'tel';
    public const string NUMBER = 'number';
    public const string DATE = 'date';
    public const string SELECT = 'select';
    public const string RADIO = 'radio';
    public const string CHECKBOX = 'checkbox';

    /**
     * All types
     */
    public const array TYPES = [
        self::INPUT,
        self::TEXTAREA,
        self::URL,
        self::EMAIL,
        self::TEL,
        self::NUMBER,
        self::DATE,
        self::SELECT,
        self::RADIO,
        self::CHECKBOX,
    ];

    /**
     * Типы, у которых min/max ограничивают длину текста
     */
    private const array TEXT_TYPES = [
        self::INPUT,
        self::TEXTAREA,
        self::URL,
        self::EMAIL,
        self::TEL,
    ];

    /**
     * Типы с примером заполнения и ограничением min/max: у текста это длина, у числа — диапазон
     */
    public const array LIMIT_TYPES = [...self::TEXT_TYPES, self::NUMBER];

    /**
     * Типы с вариантами ответа
     */
    public const array OPTION_TYPES = [self::SELECT, self::RADIO];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sort',
        'type',
        'name',
        'placeholder',
        'hint',
        'options',
        'min',
        'max',
        'required',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'placeholder' => TextCast::class,
            'hint'        => TextCast::class,
            'options'     => TextCast::class . ':nullable',
            'required'    => 'bool',
        ];
    }

    /**
     * Scope для получения полей с данными пользователя
     */
    #[Scope]
    protected function withUserData(Builder $query, int $userId): void
    {
        $query->select('user_fields.*', 'user_data.value')
            ->leftJoin('user_data', static function (JoinClause $join) use ($userId) {
                $join->on('user_fields.id', 'user_data.field_id')
                    ->where('user_data.user_id', $userId);
            })
            ->orderBy('user_fields.sort');
    }

    /**
     * Ограничивают ли min/max длину текста (у числа это диапазон, у даты и списка не используются)
     */
    public function isText(): bool
    {
        return in_array($this->type, self::TEXT_TYPES, true);
    }

    /**
     * Варианты списка, по одному на строку
     *
     * @return array<int, string>
     */
    public function optionList(): array
    {
        return self::parseOptions($this->options);
    }

    /**
     * Разбирает варианты: по одному на строку, пустые строки и пробелы по краям отбрасываются
     *
     * @return array<int, string>
     */
    public static function parseOptions(?string $options): array
    {
        $lines = preg_split('/\R/', (string) $options) ?: [];

        return array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));
    }

    /**
     * Проверяет значение по типу поля
     */
    public function validateValue(mixed $value, Validator $validator, bool $required): void
    {
        $key = 'field' . $this->id;

        if (blank($value)) {
            if ($required) {
                $validator->addError([$key => __('user_field::user_fields.required_value')]);
            }

            return;
        }

        if ($this->isText()) {
            $validator->length($value, $this->min, $this->max, [$key => __('validator.text')]);
        }

        switch ($this->type) {
            case self::URL:
                $validator->url($value, [$key => __('validator.url')]);
                break;
            case self::EMAIL:
                $validator->email($value, [$key => __('validator.email')]);
                break;
            case self::TEL:
                $validator->regex($value, '#^\+?[\d\s()\-]{5,20}$#', [$key => __('validator.phone')]);
                break;
            case self::NUMBER:
                if (is_numeric($value)) {
                    $validator->between((float) $value, $this->min, $this->max, [$key => __('user_field::user_fields.invalid_number')]);
                } else {
                    $validator->addError([$key => __('user_field::user_fields.invalid_number')]);
                }
                break;
            case self::DATE:
                if (! $this->isDate((string) $value)) {
                    $validator->addError([$key => __('user_field::user_fields.invalid_date')]);
                }
                break;
            case self::SELECT:
            case self::RADIO:
                $validator->in($value, $this->optionList(), [$key => __('user_field::user_fields.invalid_option')]);
                break;
            case self::CHECKBOX:
                // Включённый переключатель приходит единицей, выключенный не приходит вовсе
                $validator->in($value, ['1'], [$key => __('user_field::user_fields.invalid_option')]);
                break;
        }
    }

    /**
     * Санитайзит значение в зависимости от типа поля
     */
    public function sanitizeValue(?string $value): ?string
    {
        if ($this->type === self::TEXTAREA) {
            return HtmlSanitizer::sanitize($value);
        }

        return $value;
    }

    /**
     * Значение для анкеты, готовый HTML
     *
     * Ссылкой становится только то, что похоже на ссылку: у поля могли сменить тип,
     * и старые значения остались бы произвольным текстом
     */
    public function displayValue(): string
    {
        $value = (string) $this->getAttribute('value');

        return match (true) {
            $this->type === self::TEXTAREA                                         => (string) renderHtml($value),
            $this->type === self::URL && preg_match('#^https?://#i', $value) === 1 => '<a href="' . e($value) . '" target="_blank" rel="nofollow noopener">' . e($value) . '</a>',
            $this->type === self::TEL                                              => '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $value)) . '">' . e($value) . '</a>',
            $this->type === self::DATE && $this->isDate($value)                    => e(CarbonImmutable::parse($value)->format('d.m.Y')),
            $this->type === self::CHECKBOX                                         => e(__('main.yes')),
            default                                                                => e($value),
        };
    }

    /**
     * Возвращает данные
     */
    public function data(): HasMany
    {
        return $this->hasMany(UserData::class, 'field_id');
    }

    /**
     * Дата из поля type="date" приходит как ГГГГ-ММ-ДД
     */
    private function isDate(string $value): bool
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}
