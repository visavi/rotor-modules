<?php

declare(strict_types=1);

namespace Modules\PageEditor\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class CodeSearcher
{
    /**
     * Ищет строку в файлах корня
     *
     * Обход простой: без FOLLOW_SYMLINKS, без защиты от петель — поиск идёт
     * только по конфигурируемым корням (views, lang), символьных ссылок там нет.
     *
     * В режиме regex шаблон проверяется один раз до обхода файлов: если он
     * некорректен (например "[" или незакрытая группа), обход не запускается,
     * а признак invalid=true отдаётся вызывающему коду, чтобы он мог показать
     * пользователю понятное сообщение вместо "ничего не найдено".
     *
     * @return array{results: list<array{file: string, line: int, text: string}>, truncated: bool, invalid: bool}
     */
    public static function search(
        string $root,
        string $query,
        string $mask = '*',
        bool $caseSensitive = false,
        bool $regex = false,
    ): array {
        $searchRoots = config('page_editor.search_roots', []);

        if (! in_array($root, $searchRoots, true)) {
            abort(404);
        }

        $base = PathResolver::rootPath($root);
        $maxSize = (int) config('page_editor.max_search_size', 1048576);
        $maxResults = (int) config('page_editor.max_search_results', 500);

        $results = [];
        $truncated = false;
        $invalid = false;

        if ($query === '' || ! is_dir($base)) {
            return compact('results', 'truncated', 'invalid');
        }

        $pattern = null;

        if ($regex) {
            $pattern = '~' . str_replace('~', '\~', $query) . '~u' . ($caseSensitive ? '' : 'i');

            if (@preg_match($pattern, '') === false) {
                return ['results' => $results, 'truncated' => $truncated, 'invalid' => true];
            }
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getSize() > $maxSize) {
                continue;
            }

            if ($mask !== '' && $mask !== '*' && ! fnmatch($mask, $file->getFilename())) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            // бинарные файлы отсеиваются по нулевому байту в первых 8 КБ
            if (str_contains(substr($contents, 0, 8192), "\0")) {
                continue;
            }

            foreach (explode("\n", $contents) as $number => $line) {
                $matched = $pattern !== null
                    ? preg_match($pattern, $line) === 1
                    : ($caseSensitive ? str_contains($line, $query) : stripos($line, $query) !== false);

                if (! $matched) {
                    continue;
                }

                if (count($results) >= $maxResults) {
                    return ['results' => $results, 'truncated' => true, 'invalid' => false];
                }

                $results[] = [
                    'file' => trim(str_replace($base, '', $file->getPathname()), '/'),
                    'line' => $number + 1,
                    'text' => mb_substr(trim($line), 0, 300),
                ];
            }
        }

        return compact('results', 'truncated', 'invalid');
    }

    /**
     * Подсвечивает вхождения запроса в найденной строке
     *
     * Возвращает готовый HTML: куски вокруг совпадений экранируются здесь,
     * поэтому во вьюхе строка выводится без экранирования
     */
    public static function highlight(string $text, string $query, bool $caseSensitive = false, bool $regex = false): string
    {
        if ($query === '') {
            return e($text);
        }

        $pattern = $regex
            ? '~' . str_replace('~', '\~', $query) . '~u'
            : '~' . preg_quote($query, '~') . '~u';

        if (! $caseSensitive) {
            $pattern .= 'i';
        }

        if (@preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE) === false) {
            return e($text);
        }

        $html = '';
        $offset = 0;

        foreach ($matches[0] as [$match, $position]) {
            // Пустое совпадение (например regex "a*") сдвига не даёт и зациклило бы вывод
            if ($match === '') {
                continue;
            }

            $html .= e(substr($text, $offset, $position - $offset)) . '<mark>' . e($match) . '</mark>';
            $offset = $position + strlen($match);
        }

        return $html . e(substr($text, $offset));
    }
}
