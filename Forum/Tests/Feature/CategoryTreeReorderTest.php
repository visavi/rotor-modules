<?php

declare(strict_types=1);

namespace Modules\Forum\Tests\Feature;

use App\Support\CategoryTree;
use Modules\Forum\Models\Forum;
use Tests\ModuleTestCase;

class CategoryTreeReorderTest extends ModuleTestCase
{
    protected string $moduleName = 'Forum';

    public function testReorderSetsParentAndSort(): void
    {
        $first = Forum::query()->create(['title' => 'First', 'sort' => 1]);
        $second = Forum::query()->create(['title' => 'Second', 'sort' => 2]);

        CategoryTree::reorder(Forum::class, "{$second->id}:0,{$first->id}:{$second->id}");

        self::assertSame(0, (int) $second->fresh()->parent_id);
        self::assertSame(1, (int) $second->fresh()->sort);
        self::assertSame($second->id, (int) $first->fresh()->parent_id);
        self::assertSame(2, (int) $first->fresh()->sort);
    }

    public function testReorderMovesBranchWithChildren(): void
    {
        $root = Forum::query()->create(['title' => 'Root', 'sort' => 1]);
        $other = Forum::query()->create(['title' => 'Other', 'sort' => 2]);
        $child = Forum::query()->create(['title' => 'Child', 'parent_id' => $root->id, 'sort' => 3]);

        CategoryTree::reorder(Forum::class, "{$other->id}:0,{$root->id}:{$other->id},{$child->id}:{$root->id}");

        self::assertSame($other->id, (int) $root->fresh()->parent_id);
        self::assertSame($root->id, (int) $child->fresh()->parent_id);
    }

    public function testReorderIgnoresCycle(): void
    {
        $first = Forum::query()->create(['title' => 'First', 'sort' => 1]);
        $second = Forum::query()->create(['title' => 'Second', 'parent_id' => $first->id, 'sort' => 2]);

        CategoryTree::reorder(Forum::class, "{$first->id}:{$second->id},{$second->id}:{$first->id}");

        self::assertSame(0, (int) $first->fresh()->parent_id);
        self::assertSame($first->id, (int) $second->fresh()->parent_id);
    }

    public function testReorderIgnoresCycleThroughDatabaseState(): void
    {
        $parent = Forum::query()->create(['title' => 'Parent', 'sort' => 1]);
        $child = Forum::query()->create(['title' => 'Child', 'parent_id' => $parent->id, 'sort' => 2]);

        // Пара переворачивает связь, но вторую половину кольца строка не присылает:
        // цикл виден только вместе с текущим состоянием базы
        CategoryTree::reorder(Forum::class, "{$parent->id}:{$child->id}");

        self::assertSame(0, (int) $parent->fresh()->parent_id);
        self::assertSame(1, (int) $parent->fresh()->sort);
        self::assertSame($parent->id, (int) $child->fresh()->parent_id);
        self::assertSame(2, (int) $child->fresh()->sort);
    }

    public function testReorderIgnoresUnknownIdAndParent(): void
    {
        $forum = Forum::query()->create(['title' => 'Forum', 'sort' => 5]);

        CategoryTree::reorder(Forum::class, "999999:0,{$forum->id}:888888,мусор");

        self::assertSame(0, (int) $forum->fresh()->parent_id);
        self::assertSame(5, (int) $forum->fresh()->sort);
    }

    public function testReorderKeepsRecordsOutsideOrder(): void
    {
        $listed = Forum::query()->create(['title' => 'Listed', 'sort' => 1]);
        $untouched = Forum::query()->create(['title' => 'Untouched', 'parent_id' => $listed->id, 'sort' => 7]);

        CategoryTree::reorder(Forum::class, "{$listed->id}:0");

        self::assertSame($listed->id, (int) $untouched->fresh()->parent_id);
        self::assertSame(7, (int) $untouched->fresh()->sort);
    }
}
