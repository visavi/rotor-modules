<?php

declare(strict_types=1);

namespace Modules\Load\Services;

class ZipTree
{
    /**
     * Строит дерево из плоского списка файлов архива
     */
    public static function build(array $flat): array
    {
        /** @var array{__files: array[], __dirs: array<string, array>, __count: int, __size: int} $tree */
        $tree = ['__files' => [], '__dirs' => [], '__count' => 0, '__size' => 0];

        foreach ($flat as $entry) {
            $path = rtrim($entry['name'], '/');
            $parts = explode('/', $path);
            $node = &$tree;

            $depth = count($parts);
            for ($j = 0; $j < $depth; $j++) {
                $part = $parts[$j];
                $isLast = ($j === $depth - 1);

                if ($isLast && ! $entry['isDir']) {
                    $node['__files'][] = array_merge($entry, ['basename' => $part]);
                } else {
                    if (! isset($node['__dirs'][$part])) {
                        $node['__dirs'][$part] = ['__files' => [], '__dirs' => [], '__count' => 0, '__size' => 0];
                    }
                    $node = &$node['__dirs'][$part];
                }
            }
            unset($node);
        }

        self::computeStats($tree);

        return $tree;
    }

    /**
     * Рекурсивно вычисляет количество файлов и суммарный размер
     */
    private static function computeStats(array &$tree): void
    {
        $tree['__count'] = count($tree['__files']);
        $tree['__size'] = (int) array_sum(array_column($tree['__files'], 'size'));

        foreach ($tree['__dirs'] as &$subtree) {
            self::computeStats($subtree);
            $tree['__count'] += $subtree['__count'];
            $tree['__size'] += $subtree['__size'];
        }
        unset($subtree);
    }
}
