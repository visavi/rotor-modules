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

        $tree->sortAndCount();

        return $tree;
    }

    /**
     * Рекурсивно сортирует узел и вычисляет количество файлов и суммарный размер
     *
     * Порядок записей в архиве задаёт архиватор, поэтому папки и файлы
     * сортируются по имени: натурально и без учёта регистра.
     * Имя папки из цифр PHP хранит ключом-числом — отсюда приведение к строке
     */
    private function sortAndCount(): void
    {
        uksort($this->dirs, static fn (int|string $a, int|string $b): int => strnatcasecmp((string) $a, (string) $b));
        usort($this->files, static fn (array $a, array $b): int => strnatcasecmp($a['basename'], $b['basename']));

        $this->count = count($this->files);
        $this->size = (int) array_sum(array_column($this->files, 'size'));

        foreach ($this->dirs as $subtree) {
            $subtree->sortAndCount();
            $this->count += $subtree->count;
            $this->size += $subtree->size;
        }
    }
}
