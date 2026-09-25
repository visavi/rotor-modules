<?php

declare(strict_types=1);

namespace Modules\Antimat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class Antimat
 *
 * @property int    $id
 * @property string $string
 */
class Antimat extends Model
{
    /**
     * Ключ кеша со скомпилированными шаблонами
     */
    private const string CACHE_KEY = 'antimat.patterns';

    /**
     * Слов в одном регулярном выражении: у PCRE ограничен размер шаблона
     */
    private const int CHUNK = 300;

    /**
     * The table associated with the model.
     */
    protected $table = 'antimat';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Сбрасывает кеш шаблонов при изменении списка
     */
    protected static function booted(): void
    {
        static::saved(static fn () => self::flushCache());
        static::deleted(static fn () => self::flushCache());
    }

    /**
     * Заменяет слова из списка в тексте
     *
     * Вызывается на каждое чтение текстового атрибута, поэтому шаблоны
     * собираются один раз и живут в кеше, а в пределах запроса — в памяти
     */
    public static function replace(string $str): string
    {
        $wholeWord = (bool) setting('antimat_whole_word');
        $patterns = Cache::memo()->rememberForever(
            self::CACHE_KEY . '.' . (int) $wholeWord,
            static fn (): array => self::buildPatterns($wholeWord),
        );

        if (! $patterns) {
            return $str;
        }

        // Пустая настройка приходит из setting() как null — берём значение по умолчанию
        $replacement = (string) (setting('antimat_replace') ?? '***');

        return preg_replace($patterns, addcslashes($replacement, '\\$'), $str) ?? $str;
    }

    /**
     * Сбрасывает кеш шаблонов
     *
     * Массовое удаление через query() событий модели не вызывает — после него звать вручную
     */
    public static function flushCache(): void
    {
        Cache::memo()->forget(self::CACHE_KEY . '.0');
        Cache::memo()->forget(self::CACHE_KEY . '.1');
    }

    /**
     * Собирает регулярные выражения из списка слов
     *
     * Длинные слова идут первыми, чтобы короткое не съело часть длинного.
     * Режим целых слов не трогает «корабля», если в списке есть часть этого слова
     *
     * @return list<string>
     */
    private static function buildPatterns(bool $wholeWord): array
    {
        $words = self::query()
            ->orderByDesc(DB::raw('CHAR_LENGTH(string)'))
            ->pluck('string')
            ->all();

        $patterns = [];

        foreach (array_chunk($words, self::CHUNK) as $chunk) {
            $alternation = implode('|', array_map(static fn (string $word): string => preg_quote($word, '/'), $chunk));

            $patterns[] = $wholeWord
                ? '/(?<![\p{L}\p{N}_])(?:' . $alternation . ')(?![\p{L}\p{N}_])/iu'
                : '/(?:' . $alternation . ')/iu';
        }

        return $patterns;
    }
}
