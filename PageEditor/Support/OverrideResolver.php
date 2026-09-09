<?php

declare(strict_types=1);

namespace Modules\PageEditor\Support;

use Illuminate\Support\Str;

/**
 * Связывает оригинальный файл с его правкой в resources/custom
 *
 * Шаблоны и переводы переопределяются копией в custom, поэтому редактор должен
 * показывать, какой слой открыт и куда ведёт второй
 */
class OverrideResolver
{
    /**
     * Возвращает расположение правки для оригинала либо null, если файл не переопределяется
     *
     * @return array{root: string, path: string, file: string, exists: bool}|null
     */
    public static function override(string $root, string $path, string $file): ?array
    {
        if ($file === '') {
            return null;
        }

        if ($root === 'views') {
            return self::locate('custom', 'views/' . $path, $file);
        }

        if ($root === 'lang') {
            return self::locate('custom', 'lang/' . $path, $file);
        }

        if ($root !== 'modules') {
            return null;
        }

        $segments = explode('/', trim($path, '/'));
        $module = (string) array_shift($segments);

        if ($module === '') {
            return null;
        }

        $namespace = Str::snake($module);
        $inner = implode('/', $segments);

        // Переопределяются только ресурсы модуля: у контроллеров и моделей
        // механизма подмены нет
        if (str_starts_with($inner, 'resources/views')) {
            $rest = trim(substr($inner, strlen('resources/views')), '/');

            return self::locate('custom', trim('views/' . $namespace . '/' . $rest, '/'), $file);
        }

        if (str_starts_with($inner, 'resources/lang')) {
            $rest = trim(substr($inner, strlen('resources/lang')), '/');

            return self::locate('custom', trim('lang/vendor/' . $namespace . '/' . $rest, '/'), $file);
        }

        return null;
    }

    /**
     * Возвращает расположение оригинала для файла из custom
     *
     * @return array{root: string, path: string, file: string, exists: bool}|null
     */
    public static function original(string $path, string $file): ?array
    {
        if ($file === '') {
            return null;
        }

        $segments = explode('/', trim($path, '/'));
        $area = array_shift($segments);
        $rest = implode('/', $segments);

        if ($area === 'views') {
            // Первый сегмент — ключ модуля, если такой модуль существует;
            // themes и остальные каталоги принадлежат ядру
            $module = self::module($segments[0] ?? '');

            if ($module !== null) {
                $inner = implode('/', array_slice($segments, 1));

                return self::locate('modules', trim($module . '/resources/views/' . $inner, '/'), $file);
            }

            return self::locate('views', $rest, $file);
        }

        if ($area !== 'lang') {
            return null;
        }

        if (($segments[0] ?? '') === 'vendor') {
            $module = self::module($segments[1] ?? '');

            if ($module === null) {
                return null;
            }

            $inner = implode('/', array_slice($segments, 2));

            return self::locate('modules', trim($module . '/resources/lang/' . $inner, '/'), $file);
        }

        return self::locate('lang', $rest, $file);
    }

    /**
     * Собирает адрес файла и сообщает, существует ли он
     *
     * @return array{root: string, path: string, file: string, exists: bool}
     */
    private static function locate(string $root, string $path, string $file): array
    {
        $path = trim(PathResolver::normalize($path), '/');

        return [
            'root'   => $root,
            'path'   => $path,
            'file'   => $file,
            'exists' => is_file(PathResolver::resolve($root, trim($path . '/' . $file, '/'))),
        ];
    }

    /**
     * Возвращает имя каталога модуля по его ключу
     */
    private static function module(string $namespace): ?string
    {
        if ($namespace === '') {
            return null;
        }

        foreach (glob(base_path('modules/*'), GLOB_ONLYDIR) ?: [] as $directory) {
            if (Str::snake(basename($directory)) === $namespace) {
                return basename($directory);
            }
        }

        return null;
    }
}
