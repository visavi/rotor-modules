<?php

declare(strict_types=1);

namespace Modules\PageEditor\Support;

class PhpArrayDumper
{
    /**
     * Возвращает содержимое PHP-файла с массивом
     */
    public static function dump(array $data): string
    {
        return "<?php\n\nreturn " . self::export($data, 0) . ";\n";
    }

    /**
     * Рекурсивно экспортирует массив в короткий синтаксис
     */
    private static function export(array $data, int $depth): string
    {
        if ($data === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth + 1);
        $lines = [];
        $list = array_is_list($data);

        foreach ($data as $key => $value) {
            $prefix = $list ? '' : self::quote((string) $key) . ' => ';

            $lines[] = $indent . $prefix . (is_array($value)
                ? self::export($value, $depth + 1)
                : self::scalar($value));
        }

        return "[\n" . implode(",\n", $lines) . ",\n" . str_repeat('    ', $depth) . ']';
    }

    /**
     * Экспортирует скалярное значение
     */
    private static function scalar(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            is_int($value),
            is_float($value) => (string) $value,
            default          => self::quote((string) $value),
        };
    }

    /**
     * Оборачивает строку в одинарные кавычки
     */
    private static function quote(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    }
}
