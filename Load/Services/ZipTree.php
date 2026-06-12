<?php

declare(strict_types=1);

namespace Modules\Load\Services;

/**
 * Дерево файлов архива: каждый экземпляр — узел (каталог)
 */
class ZipTree
{
    /** @var array<int, array<string, mixed>> */
    public array $files = [];
    /** @var array<string, self> */
    public array $dirs = [];
    public int $count = 0;
    public int $size = 0;

    /**
     * Строит дерево из плоского списка файлов архива
     */
    public static function build(array $flat): self
    {
        $tree = new self();

        foreach ($flat as $entry) {
            $path = rtrim($entry['name'], '/');
            $parts = explode('/', $path);
            $node = $tree;

            $depth = count($parts);
            for ($j = 0; $j < $depth; $j++) {
                $part = $parts[$j];
                $isLast = ($j === $depth - 1);

                if ($isLast && ! $entry['isDir']) {
                    $node->files[] = array_merge($entry, ['basename' => $part]);
                } else {
                    $node->dirs[$part] ??= new self();
                    $node = $node->dirs[$part];
                }
            }
        }

        $tree->computeStats();

        return $tree;
    }

    /**
     * Рекурсивно вычисляет количество файлов и суммарный размер
     */
    private function computeStats(): void
    {
        $this->count = count($this->files);
        $this->size = (int) array_sum(array_column($this->files, 'size'));

        foreach ($this->dirs as $subtree) {
            $subtree->computeStats();
            $this->count += $subtree->count;
            $this->size += $subtree->size;
        }
    }
}
