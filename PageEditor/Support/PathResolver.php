<?php

declare(strict_types=1);

namespace Modules\PageEditor\Support;

use Illuminate\Support\Str;

class PathResolver
{
    /**
     * Возвращает список корней
     *
     * @return array<string, string>
     */
    public static function roots(): array
    {
        return config('page_editor.roots', []);
    }

    /**
     * Возвращает корни, доступные поиску: редактируемые плюс read-only
     *
     * @return array<string, string>
     */
    public static function searchRoots(): array
    {
        return self::roots() + config('page_editor.search_only_roots', []);
    }

    /**
     * Сообщает, доступен ли корень только для чтения
     */
    public static function isReadOnly(string $root): bool
    {
        return ! isset(self::roots()[$root]) && isset(config('page_editor.search_only_roots', [])[$root]);
    }

    /**
     * Возвращает абсолютный путь корня
     */
    public static function rootPath(string $root): string
    {
        $roots = self::searchRoots();

        if (! isset($roots[$root])) {
            abort(404);
        }

        return rtrim($roots[$root], '/');
    }

    /**
     * Нормализует путь лексически
     *
     * realpath() здесь неприменим: modules/* — символьные ссылки наружу проекта,
     * и проверка вхождения в корень отклонила бы все модули
     */
    public static function normalize(string $path): string
    {
        $segments = [];

        // Нулевые байты вырезаем сразу: иначе is_file()/filesize() бросают ValueError
        $path = str_replace(['\\', "\0"], ['/', ''], $path);

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    /**
     * Возвращает абсолютный путь внутри корня
     */
    public static function resolve(string $root, string $path = ''): string
    {
        $base = self::rootPath($root);
        $relative = self::normalize($path);

        return $relative === '' ? $base : $base . '/' . $relative;
    }

    /**
     * Возвращает расширение файла
     */
    public static function extension(string $file): string
    {
        $file = basename($file);

        if (Str::endsWith($file, '.blade.php')) {
            return 'blade.php';
        }

        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * Проверяет, открывается ли файл в редакторе
     */
    public static function isEditable(string $file): bool
    {
        return in_array(self::extension($file), config('page_editor.editable', []), true);
    }
}
