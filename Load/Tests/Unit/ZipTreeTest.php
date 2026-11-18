<?php

namespace Modules\Load\Tests\Unit;

use Modules\Load\Services\ZipTree;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ZipTree::class)]
class ZipTreeTest extends TestCase
{
    public function testNodesAreSortedByName(): void
    {
        // Порядок записей в архиве — как их записал архиватор
        $tree = ZipTree::build($this->flat([
            'vendor/b.php',
            'readme.md',
            'App/z.php',
            'vendor/a.php',
            'file10.txt',
            '.htaccess',
            'database/',
            'file2.txt',
            'Composer.json',
            'app-extra/x.php',
        ]));

        $this->assertSame(['App', 'app-extra', 'database', 'vendor'], array_keys($tree->dirs));
        $this->assertSame(
            ['.htaccess', 'Composer.json', 'file2.txt', 'file10.txt', 'readme.md'],
            array_column($tree->files, 'basename'),
        );
        $this->assertSame(['a.php', 'b.php'], array_column($tree->dirs['vendor']->files, 'basename'));
    }

    public function testNumericFolderNamesAreSortedNaturally(): void
    {
        // Ключ-число в массиве: «2024» хранится как int
        $tree = ZipTree::build($this->flat(['2024/a.txt', 'img/b.png', '9/c.txt', '10/d.txt']));

        $this->assertSame([9, 10, 2024, 'img'], array_keys($tree->dirs));
    }

    public function testStatsSurviveSorting(): void
    {
        $tree = ZipTree::build($this->flat(['b/one.txt', 'a.txt', 'b/c/two.txt']));

        $this->assertSame(3, $tree->count);
        $this->assertSame(30, $tree->size);
        $this->assertSame(2, $tree->dirs['b']->count);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function flat(array $names): array
    {
        return array_map(static fn (string $name, int $index): array => [
            'index' => $index,
            'name'  => $name,
            'size'  => 10,
            'isDir' => str_ends_with($name, '/'),
            'ext'   => pathinfo($name, PATHINFO_EXTENSION),
        ], $names, array_keys($names));
    }
}
