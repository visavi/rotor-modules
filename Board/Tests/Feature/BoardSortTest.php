<?php

declare(strict_types=1);

namespace Modules\Board\Tests\Feature;

use App\Models\User;
use Modules\Board\Models\Board;
use Tests\ModuleTestCase;

class BoardSortTest extends ModuleTestCase
{
    protected string $moduleName = 'Board';

    public function testAdminBelowBossCannotSort(): void
    {
        $admin = User::factory()->admin()->create();
        $board = Board::query()->create(['name' => 'Board', 'sort' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.boards.sort'), ['order' => "{$board->id}:0"])
            ->assertForbidden();
    }

    public function testBossMovesBranchWithChildren(): void
    {
        $boss = User::factory()->boss()->create();

        $root = Board::query()->create(['name' => 'Root', 'sort' => 1]);
        $other = Board::query()->create(['name' => 'Other', 'sort' => 2]);
        $child = Board::query()->create(['name' => 'Child', 'parent_id' => $root->id, 'sort' => 3]);

        $this->actingAs($boss)
            ->post(route('admin.boards.sort'), [
                'order' => "{$other->id}:0,{$root->id}:{$other->id},{$child->id}:{$root->id}",
            ])
            ->assertRedirect(route('admin.boards.categories'))
            ->assertSessionHas('success');

        self::assertSame($other->id, (int) $root->fresh()->parent_id);
        self::assertSame($root->id, (int) $child->fresh()->parent_id);
    }

    public function testCategoriesShowThirdLevelAndSubtreeTotals(): void
    {
        $boss = User::factory()->boss()->create();

        $root = Board::query()->create(['name' => 'Root board', 'sort' => 1, 'count_items' => 1]);
        $child = Board::query()->create(['name' => 'Child board', 'parent_id' => $root->id, 'sort' => 2, 'count_items' => 2]);
        Board::query()->create(['name' => 'Grandchild board', 'parent_id' => $child->id, 'sort' => 3, 'count_items' => 4]);

        $this->actingAs($boss)
            ->get(route('admin.boards.categories'))
            ->assertOk()
            ->assertSee('Grandchild board')
            // Счётчик корня складывает всю ветку: 1 + 2 + 4
            ->assertSee('>7</span>', false);
    }
}
