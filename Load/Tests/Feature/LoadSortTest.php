<?php

declare(strict_types=1);

namespace Modules\Load\Tests\Feature;

use App\Models\User;
use Modules\Load\Models\Load;
use Tests\ModuleTestCase;

class LoadSortTest extends ModuleTestCase
{
    protected string $moduleName = 'Load';

    public function testAdminBelowBossCannotSort(): void
    {
        $admin = User::factory()->admin()->create();
        $load = Load::query()->create(['name' => 'Load', 'sort' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.loads.sort'), ['order' => "{$load->id}:0"])
            ->assertForbidden();
    }

    public function testBossMovesBranchWithChildren(): void
    {
        $boss = User::factory()->boss()->create();

        $root = Load::query()->create(['name' => 'Root', 'sort' => 1]);
        $other = Load::query()->create(['name' => 'Other', 'sort' => 2]);
        $child = Load::query()->create(['name' => 'Child', 'parent_id' => $root->id, 'sort' => 3]);

        $this->actingAs($boss)
            ->post(route('admin.loads.sort'), [
                'order' => "{$other->id}:0,{$root->id}:{$other->id},{$child->id}:{$root->id}",
            ])
            ->assertRedirect(route('admin.loads.index'))
            ->assertSessionHas('success');

        self::assertSame($other->id, (int) $root->fresh()->parent_id);
        self::assertSame($root->id, (int) $child->fresh()->parent_id);
    }

    public function testIndexShowsThirdLevelAndSubtreeTotals(): void
    {
        $boss = User::factory()->boss()->create();

        $root = Load::query()->create(['name' => 'Root load', 'sort' => 1, 'count_downs' => 1]);
        $child = Load::query()->create(['name' => 'Child load', 'parent_id' => $root->id, 'sort' => 2, 'count_downs' => 2]);
        Load::query()->create(['name' => 'Grandchild load', 'parent_id' => $child->id, 'sort' => 3, 'count_downs' => 4]);

        $this->actingAs($boss)
            ->get(route('admin.loads.index'))
            ->assertOk()
            ->assertSee('Grandchild load')
            // Счётчик корня складывает всю ветку: 1 + 2 + 4
            ->assertSee('>7</span>', false);
    }

    public function testIndexHidesSortFormFromAdminBelowBoss(): void
    {
        $admin = User::factory()->admin()->create();

        Load::query()->create(['name' => 'Root load', 'sort' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.loads.index'))
            ->assertOk()
            ->assertSee('Root load')
            ->assertDontSee('data-sortable-tree', false);
    }
}
