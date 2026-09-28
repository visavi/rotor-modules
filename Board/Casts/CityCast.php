<?php

declare(strict_types=1);

namespace Modules\Board\Casts;

use App\Casts\TextCast;
use Illuminate\Support\Str;

/**
 * Город объявления: свободный ввод без справочника
 *
 * Лишние пробелы убираются, первая буква заглавная — « москва» и «Москва»
 * сливаются в один город. Остальной регистр не трогаем, иначе «Нью-Йорк»
 * стал бы «Нью-йорк». Сравнение в MySQL и так без учёта регистра
 */
class CityCast extends TextCast
{
    protected function input(string $value): string
    {
        return Str::ucfirst(preg_replace('/\s+/u', ' ', trim($value)));
    }
}
