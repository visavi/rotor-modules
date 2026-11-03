<?php

declare(strict_types=1);

namespace Modules\Privacy\Models;

use App\Casts\HtmlCast;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Версия текста согласия на обработку персональных данных
 *
 * Версии только добавляются: по text_id согласия видно, какой именно текст принял пользователь
 *
 * @property int             $id
 * @property string          $text
 * @property CarbonImmutable $created_at
 */
class PrivacyText extends Model
{
    /** @use HasFactory<\Modules\Privacy\Tests\Factories\PrivacyTextFactory> */
    use HasFactory;

    /**
     * The name of the "updated at" column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'text' => HtmlCast::class,
        ];
    }

    /**
     * Согласия, данные на эту версию
     */
    public function consents(): HasMany
    {
        return $this->hasMany(PrivacyConsent::class, 'text_id');
    }

    /**
     * Действующая версия. Пока владелец не правил текст, первая собирается из шаблона модуля
     */
    public static function current(): self
    {
        return self::query()->latest('id')->first() ?? self::query()->create([
            'text' => view('privacy::_template', [
                'site'   => setting('title'),
                'url'    => url('/'),
                'locale' => setting('language'),
            ])->render(),
            'created_at' => now(),
        ]);
    }

    /**
     * Текст как сохранён, без фильтров вывода: для редактора и сравнения версий
     */
    public function rawText(): string
    {
        return (string) $this->getRawOriginal('text');
    }

    /**
     * Тот же текст, что у другой версии
     *
     * Пробелы и переводы строк между тегами не в счёт: редактор склеивает абзацы
     * шаблона в одну строку, и пересохранение без правок плодило бы версию
     */
    public function sameTextAs(self $other): bool
    {
        $normalize = static fn (string $html): string => trim((string) preg_replace('/>\s+</', '><', $html));

        return $normalize((string) $this->getAttributes()['text']) === $normalize($other->rawText());
    }
}
