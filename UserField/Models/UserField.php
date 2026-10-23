<?php

declare(strict_types=1);

namespace Modules\UserField\Models;

use App\Casts\TextCast;
use App\Rules\EmailRule;
use App\Support\HtmlSanitizer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

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
    /** @use HasFactory<\Modules\UserField\Tests\Factories\UserFieldFactory> */
    use HasFactory;

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
     *
     * @return string|null текст ошибки или null, если значение подходит
     */
    public function validateValue(mixed $value, bool $required): ?string
    {
        $key = $this->errorKey($value, $required);

        return $key === null ? null : (string) __($key);
    }

    /**
     * Ключ перевода ошибки или null, если значение подходит
     */
    private function errorKey(mixed $value, bool $required): ?string
    {
        if (blank($value)) {
            return $required ? 'user_field::user_fields.required_value' : null;
        }

        if (! is_scalar($value)) {
            return 'validator.text';
        }

        $value = (string) $value;

        // Длина без тегов: у многострочного поля значение — HTML из редактора
        if ($this->isText()) {
            $length = mb_strlen(strip_tags($value));

            if ($length < $this->min || $length > $this->max) {
                return 'validator.text';
            }
        }

        return match ($this->type) {
            self::URL    => $this->passes($value, 'url:http,https') ? null : 'validator.url',
            self::EMAIL  => $this->passes($value, new EmailRule()) ? null : 'validator.email',
            self::TEL    => preg_match('#^\+?[\d\s()\-]{5,20}$#', $value) ? null : 'validator.phone',
            self::NUMBER => is_numeric($value) && $value >= $this->min && $value <= $this->max
                ? null
                : 'user_field::user_fields.invalid_number',
            self::DATE => $this->isDate($value) ? null : 'user_field::user_fields.invalid_date',
            self::SELECT,
            self::RADIO => in_array($value, $this->optionList(), true) ? null : 'user_field::user_fields.invalid_option',
            // Включённый переключатель приходит единицей, выключенный не приходит вовсе
            self::CHECKBOX => $value === '1' ? null : 'user_field::user_fields.invalid_option',
            default        => null,
        };
    }

    /**
     * Значение проходит правило Laravel
     */
    private function passes(string $value, mixed $rule): bool
    {
        return Validator::make(['value' => $value], ['value' => [$rule]])->passes();
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
