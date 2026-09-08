<?php

declare(strict_types=1);

namespace Modules\PageEditor\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TranslationRepository
{
    /**
     * Возвращает контейнер кэша, живущий вместе с текущим приложением
     *
     * Кэш привязан к контейнеру, а не к статическому свойству класса: контейнер
     * пересоздаётся на каждый запрос (и на каждый тест в тестовом рантайме),
     * поэтому кэш никогда не переживает границу запроса
     */
    private static function cache(): \stdClass
    {
        if (! app()->bound(self::class)) {
            app()->instance(self::class, (object) [
                'locales' => null,
                'groups'  => null,
                'lines'   => [],
            ]);
        }

        /** @var \stdClass $cache */
        $cache = app()->get(self::class);

        return $cache;
    }

    /**
     * Возвращает корень overlay
     */
    public static function overlayRoot(): string
    {
        return (string) config('page_editor.overlay_path', resource_path('custom/lang'));
    }

    /**
     * Возвращает список локалей
     *
     * @return list<string>
     */
    public static function locales(): array
    {
        $cache = self::cache();

        if ($cache->locales !== null) {
            return $cache->locales;
        }

        $locales = [];

        foreach (glob(resource_path('lang/*'), GLOB_ONLYDIR) ?: [] as $directory) {
            $locales[] = basename($directory);
        }

        sort($locales);

        return $cache->locales = $locales;
    }

    /**
     * Возвращает список групп переводов
     *
     * @return list<array{namespace: ?string, group: string, label: string, enabled: bool}>
     */
    public static function groups(): array
    {
        $cache = self::cache();

        if ($cache->groups !== null) {
            return $cache->groups;
        }

        $groups = [];
        $locales = self::locales();

        foreach ($locales as $locale) {
            foreach (glob(resource_path('lang/' . $locale . '/*.php')) ?: [] as $file) {
                $group = basename($file, '.php');
                $groups[$group] = ['namespace' => null, 'group' => $group, 'label' => $group, 'enabled' => true];
            }
        }

        // Показываем и выключенные модули — их правки применятся после включения,
        // но помечаем, чтобы отсутствие эффекта не выглядело поломкой
        $enabled = self::enabledNamespaces();

        foreach (self::moduleNamespaces() as $namespace => $path) {
            foreach ($locales as $locale) {
                foreach (glob($path . '/' . $locale . '/*.php') ?: [] as $file) {
                    $group = basename($file, '.php');
                    $label = $namespace . '::' . $group;
                    $groups[$label] = [
                        'namespace' => $namespace,
                        'group'     => $group,
                        'label'     => $label,
                        'enabled'   => isset($enabled[$namespace]),
                    ];
                }
            }
        }

        // Ядро сверху, модули снизу — внутри каждой части по алфавиту
        uasort($groups, static fn (array $a, array $b) => [$a['namespace'] !== null, $a['label']]
            <=> [$b['namespace'] !== null, $b['label']]);

        return $cache->groups = array_values($groups);
    }

    /**
     * Возвращает плоские строки группы по локалям, с учётом overlay
     *
     * @return array<string, array<string, string>>
     */
    public static function lines(?string $namespace, string $group): array
    {
        $cache = self::cache();
        $cacheKey = ($namespace ?? '') . '::' . $group;

        if (isset($cache->lines[$cacheKey])) {
            return $cache->lines[$cacheKey];
        }

        $lines = [];

        foreach (self::locales() as $locale) {
            // Читаем файлы напрямую, а не через translation.loader: неймспейсы
            // регистрируются только для активных модулей, и для остальных
            // загрузчик молча вернул бы пустую группу
            $loaded = array_replace(
                self::originals($namespace, $group, $locale),
                self::overlayValues($namespace, $group, $locale),
            );

            foreach ($loaded as $key => $value) {
                $lines[$key][$locale] = $value;
            }
        }

        return $cache->lines[$cacheKey] = $lines;
    }

    /**
     * Возвращает исходные строки группы без overlay
     *
     * @return array<string, string>
     */
    public static function originals(?string $namespace, string $group, string $locale): array
    {
        $file = $namespace === null
            ? resource_path('lang/' . $locale . '/' . $group . '.php')
            : self::modulePath($namespace, $group, $locale);

        if ($file === null || ! is_file($file)) {
            return [];
        }

        return self::flatten(include $file);
    }

    /**
     * Возвращает плоские значения overlay-файла группы
     *
     * @return array<string, string>
     */
    private static function overlayValues(?string $namespace, string $group, string $locale): array
    {
        $path = self::overlayPath($namespace, $group, $locale);

        if (! is_file($path)) {
            return [];
        }

        $data = include $path;

        // Каталог custom правится этим же редактором: файл, не возвращающий
        // массив, не должен ронять страницу переводов
        if (! is_array($data)) {
            return [];
        }

        return self::flatten($data);
    }

    /**
     * Разворачивает массив переводов в плоские строки
     *
     * Arr::dot оставляет пустые массивы значениями, поэтому нескалярные отбрасываем
     *
     * @param array<mixed> $data
     *
     * @return array<string, string>
     */
    private static function flatten(array $data): array
    {
        $result = [];

        foreach (Arr::dot($data) as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $result[(string) $key] = (string) $value;
            }
        }

        return $result;
    }

    /**
     * Возвращает путь к overlay-файлу группы
     */
    public static function overlayPath(?string $namespace, string $group, string $locale): string
    {
        return $namespace === null
            ? self::overlayRoot() . '/' . $locale . '/' . $group . '.php'
            : self::overlayRoot() . '/vendor/' . $namespace . '/' . $locale . '/' . $group . '.php';
    }

    /**
     * Возвращает ключи, переопределённые в overlay, по локалям
     *
     * @return array<string, array<string, bool>>
     */
    public static function overrides(?string $namespace, string $group): array
    {
        $overrides = [];

        foreach (self::locales() as $locale) {
            foreach (array_keys(self::overlayValues($namespace, $group, $locale)) as $key) {
                $overrides[(string) $key][$locale] = true;
            }
        }

        return $overrides;
    }

    /**
     * Записывает в overlay только отличия от исходных значений
     *
     * @param array<string, string> $values
     * @param list<string>          $reset  ключи, которые надо убрать из overlay
     */
    public static function save(?string $namespace, string $group, string $locale, array $values, array $reset = []): void
    {
        $path = self::overlayPath($namespace, $group, $locale);
        $originals = self::originals($namespace, $group, $locale);
        $overlay = is_file($path) ? Arr::dot(include $path) : [];

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $originals) && $originals[$key] === $value) {
                unset($overlay[$key]);
                continue;
            }

            // Ключа нет в исходниках, пришло пустое значение — это не перевод, а
            // отрисованный вьюхой пробел в локали. Записав его, мы бы убили фолбэк:
            // Translator считает пустую строку валидным переводом
            if ($value === '' && ! array_key_exists($key, $originals)) {
                unset($overlay[$key]);
                continue;
            }

            $overlay[$key] = $value;
        }

        // Сброс идёт после значений: галочка сильнее содержимого поля
        foreach ($reset as $key) {
            unset($overlay[$key]);
        }

        $result = [];

        foreach ($overlay as $key => $value) {
            Arr::set($result, $key, $value);
        }

        FileWriter::put($path, PhpArrayDumper::dump($result));

        self::invalidate();
    }

    /**
     * Сбрасывает кэш переводов — собственный (locales/groups/lines) и кэш транслятора Laravel
     *
     * save() пишет overlay-файл, из-за чего значения self::lines() и результат __() внутри
     * того же запроса устаревают. Собственный кэш живёт в контейнере (см. cache()) — забываем
     * инстанс, и он пересоберётся при следующем обращении. У Translator нет публичного способа
     * сбросить одну группу из его $loaded, поэтому сбрасываем его целиком через setLoaded([]) —
     * это дёшево, транслятор просто перечитает нужные группы при следующем __()/lines()
     */
    public static function invalidate(): void
    {
        if (app()->bound(self::class)) {
            app()->forgetInstance(self::class);
        }

        if (app()->bound('translator')) {
            app('translator')->setLoaded([]);
        }
    }

    /**
     * Ищет по ключам и значениям во всех группах
     *
     * @return list<array{namespace: ?string, group: string, key: string, values: array<string, string>}>
     */
    public static function search(string $query): array
    {
        $found = [];

        if ($query === '') {
            return $found;
        }

        foreach (self::groups() as $group) {
            foreach (self::lines($group['namespace'], $group['group']) as $key => $values) {
                $haystack = $key . ' ' . implode(' ', $values);

                if (mb_stripos($haystack, $query) !== false) {
                    $found[] = [
                        'namespace' => $group['namespace'],
                        'group'     => $group['group'],
                        'key'       => $key,
                        'values'    => $values,
                    ];
                }
            }
        }

        return $found;
    }

    /**
     * Возвращает путь к исходному языковому файлу модуля
     */
    private static function modulePath(string $namespace, string $group, string $locale): ?string
    {
        $path = self::moduleNamespaces()[$namespace] ?? null;

        return $path === null ? null : $path . '/' . $locale . '/' . $group . '.php';
    }

    /**
     * Возвращает языковые каталоги всех модулей на диске
     *
     * @return array<string, string>
     */
    private static function moduleNamespaces(): array
    {
        $namespaces = [];

        foreach (glob(base_path('modules/*'), GLOB_ONLYDIR) ?: [] as $module) {
            $path = $module . '/resources/lang';

            if (is_dir($path)) {
                $namespaces[Str::snake(basename($module))] = $path;
            }
        }

        return $namespaces;
    }

    /**
     * Возвращает неймспейсы включённых модулей
     *
     * @return array<string, string>
     */
    private static function enabledNamespaces(): array
    {
        $modules = base_path('modules') . '/';
        $namespaces = [];

        // Через translator, а не translation.loader: неймспейсы модулей
        // регистрируются лениво, при первом резолве переводчика
        foreach (app('translator')->getLoader()->namespaces() as $namespace => $path) {
            // Загрузчик держит и неймспейсы пакетов — берём только модули
            if (str_starts_with($path, $modules)) {
                $namespaces[$namespace] = $path;
            }
        }

        return $namespaces;
    }
}
