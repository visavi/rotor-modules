<?php

declare(strict_types=1);

namespace Modules\Docs\Services;

use Illuminate\Support\Str;

class DocsService
{
    public const string LARAVEL_VERSION = '13.x';

    private const array SKIP_FILES = ['documentation', 'readme', 'license'];

    /**
     * Извлекает заголовок h1 из markdown
     */
    public function extractTitle(string $raw): ?string
    {
        if (preg_match('/^#\s+(.+)$/m', $raw, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * Возвращает объединённое меню навигации
     */
    public function buildMenu(?string $rotorPage, ?string $laravelPage): array
    {
        return [
            'rotor'   => ['nav' => $this->getRotorMenu(), 'page' => $rotorPage],
            'laravel' => ['nav' => $this->getLaravelMenu(), 'page' => $laravelPage],
        ];
    }

    public function getRotorMenu(): array
    {
        $path = base_path('modules/Docs/resources/docs/navigation.md');

        return file_exists($path)
            ? $this->parseMenuMarkdown(file_get_contents($path))
            : [];
    }

    public function getLaravelMenu(): array
    {
        $path = base_path('modules/Docs/resources/laravel-docs/documentation.md');

        if (! file_exists($path)) {
            return [];
        }

        $md = Str::of(file_get_contents($path))
            ->after('---')
            ->after('---')
            ->replace('{{version}}', self::LARAVEL_VERSION)
            ->replace('/docs/' . self::LARAVEL_VERSION . '/', '/docs/')
            ->toString();

        return $this->parseMenuMarkdown($md);
    }

    /**
     * Поиск по обеим секциям документации
     */
    public function search(string $query): array
    {
        return array_merge(
            $this->searchInDir(base_path('modules/Docs/resources/docs'), $query, 'rotor'),
            $this->searchInDir(base_path('modules/Docs/resources/laravel-docs'), $query, 'laravel'),
        );
    }

    /**
     * Парсит markdown меню Laravel в массив групп
     */
    private function parseMenuMarkdown(string $md): array
    {
        $menu = [];
        $currentGroup = null;

        foreach (explode("\n", $md) as $line) {
            if (preg_match('/^- ## (.+)$/', trim($line), $m)) {
                if ($currentGroup !== null) {
                    $menu[] = $currentGroup;
                }
                $currentGroup = ['title' => trim($m[1]), 'items' => []];
            } elseif ($currentGroup !== null && preg_match('/- \[(.+)]\(([^)]+)\)/', $line, $m)) {
                $href = trim($m[2]);
                $page = basename($href);
                $currentGroup['items'][] = compact('href', 'page') + ['title' => trim($m[1])];
            }
        }

        if ($currentGroup !== null) {
            $menu[] = $currentGroup;
        }

        return $menu;
    }

    /**
     * Поиск по markdown файлам в директории
     */
    private function searchInDir(string $dir, string $query, string $type): array
    {
        $results = [];

        if (! is_dir($dir)) {
            return $results;
        }

        foreach (glob("{$dir}/*.md") as $file) {
            $base = basename($file, '.md');

            if (in_array($base, self::SKIP_FILES, true)) {
                continue;
            }

            $raw = preg_replace('/^---.*?---\s*/s', '', file_get_contents($file));

            if (mb_stripos($raw, $query) === false) {
                continue;
            }

            $title = $this->extractTitle($raw) ?? $base;
            $href = "/docs/{$base}";
            $excerpt = $this->buildExcerpt($raw, $query);

            $results[] = compact('title', 'href', 'type', 'excerpt');
        }

        return $results;
    }

    /**
     * Формирует excerpt вокруг найденного слова
     */
    private function buildExcerpt(string $raw, string $query): string
    {
        $pos = mb_stripos($raw, $query) ?: 0;
        $start = max(0, $pos - 100);

        if ($start > 0) {
            $firstSpace = mb_strpos($raw, ' ', $start);
            $start = $firstSpace !== false ? $firstSpace + 1 : $start;
        }

        $excerpt = mb_substr($raw, $start, 500);
        $excerpt = preg_replace(
            ['/`{1,3}[^`]*`{1,3}/', '/!?\[([^\]]*)]([^)]*\))/', '/[#*_>-]{2,}/'],
            ['', '$1', ''],
            strip_tags($excerpt)
        );
        $excerpt = trim(preg_replace(['/[ \t]+/', '/\n{3,}/'], [' ', "\n\n"], $excerpt));

        if (mb_strlen($excerpt) > 400) {
            $excerpt = mb_substr($excerpt, 0, mb_strrpos(mb_substr($excerpt, 0, 400), ' '));
        }

        return $excerpt;
    }
}
