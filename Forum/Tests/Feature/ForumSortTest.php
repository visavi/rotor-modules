<?php

declare(strict_types=1);

namespace Modules\Forum\Tests\Feature;

use App\Models\User;
use Modules\Forum\Models\Forum;
use Tests\ModuleTestCase;

class ForumSortTest extends ModuleTestCase
{
    protected string $moduleName = 'Forum';

    public function testNonAdminCannotSort(): void
    {
        $user = User::factory()->create();
        $forum = Forum::query()->create(['title' => 'Forum', 'sort' => 1]);

        $this->actingAs($user)
            ->post(route('admin.forums.sort'), ['order' => "{$forum->id}:0"])
            ->assertForbidden();
    }

    public function testAdminBelowBossCannotSort(): void
    {
        $admin = User::factory()->admin()->create();
        $forum = Forum::query()->create(['title' => 'Forum', 'sort' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.forums.sort'), ['order' => "{$forum->id}:0"])
            ->assertForbidden();
    }

    public function testBossMovesBranchWithChildren(): void
    {
        $boss = User::factory()->boss()->create();

        $root = Forum::query()->create(['title' => 'Root', 'sort' => 1]);
        $other = Forum::query()->create(['title' => 'Other', 'sort' => 2]);
        $child = Forum::query()->create(['title' => 'Child', 'parent_id' => $root->id, 'sort' => 3]);

        $this->actingAs($boss)
            ->post(route('admin.forums.sort'), [
                'order' => "{$other->id}:0,{$root->id}:{$other->id},{$child->id}:{$root->id}",
            ])
            ->assertRedirect(route('admin.forums.index'))
            ->assertSessionHas('success');

        self::assertSame($other->id, (int) $root->fresh()->parent_id);
        self::assertSame($root->id, (int) $child->fresh()->parent_id);
    }

    public function testIndexHidesSortFormFromAdminBelowBoss(): void
    {
        $admin = User::factory()->admin()->create();

        Forum::query()->create(['title' => 'Root forum', 'sort' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.forums.index'))
            ->assertOk()
            ->assertSee('Root forum')
            ->assertDontSee('data-sortable-tree', false);
    }

    public function testIndexShowsThirdLevel(): void
    {
        $boss = User::factory()->boss()->create();

        $root = Forum::query()->create(['title' => 'Root forum', 'sort' => 1]);
        $child = Forum::query()->create(['title' => 'Child forum', 'parent_id' => $root->id, 'sort' => 2]);
        Forum::query()->create(['title' => 'Grandchild forum', 'parent_id' => $child->id, 'sort' => 3]);

        $this->actingAs($boss)
            ->get(route('admin.forums.index'))
            ->assertOk()
            ->assertSee('Grandchild forum');
    }
}
