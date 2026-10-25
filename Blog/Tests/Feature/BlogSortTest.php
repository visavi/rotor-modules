<?php

declare(strict_types=1);

namespace Modules\Blog\Tests\Feature;

use App\Models\User;
use Modules\Blog\Models\Blog;
use Tests\ModuleTestCase;

class BlogSortTest extends ModuleTestCase
{
    protected string $moduleName = 'Blog';

    public function testAdminBelowBossCannotSort(): void
    {
        $admin = User::factory()->admin()->create();
        $blog = Blog::factory()->create(['name' => 'Blog', 'sort' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.blogs.sort'), ['order' => "{$blog->id}:0"])
            ->assertForbidden();
    }

    public function testArrayOrderIsIgnored(): void
    {
        $boss = User::factory()->boss()->create();

        $root = Blog::factory()->create(['name' => 'Root', 'sort' => 1]);
        $child = Blog::factory()->create(['name' => 'Child', 'parent_id' => $root->id, 'sort' => 2]);

        $this->actingAs($boss)
            ->post(route('admin.blogs.sort'), ['order' => ["{$child->id}:0"]])
            ->assertRedirect(route('admin.blogs.index'));

        self::assertSame($root->id, (int) $child->fresh()->parent_id);
    }

    public function testBossMovesBranchWithChildren(): void
    {
        $boss = User::factory()->boss()->create();

        $root = Blog::factory()->create(['name' => 'Root', 'sort' => 1]);
        $other = Blog::factory()->create(['name' => 'Other', 'sort' => 2]);
        $child = Blog::factory()->create(['name' => 'Child', 'parent_id' => $root->id, 'sort' => 3]);

        $this->actingAs($boss)
            ->post(route('admin.blogs.sort'), [
                'order' => "{$other->id}:0,{$root->id}:{$other->id},{$child->id}:{$root->id}",
            ])
            ->assertRedirect(route('admin.blogs.index'))
            ->assertSessionHas('success');

        self::assertSame($other->id, (int) $root->fresh()->parent_id);
        self::assertSame($root->id, (int) $child->fresh()->parent_id);
    }

    public function testIndexShowsThirdLevelAndSubtreeTotals(): void
    {
        $boss = User::factory()->boss()->create();

        $root = Blog::factory()->create(['name' => 'Root blog', 'sort' => 1, 'count_articles' => 1]);
        $child = Blog::factory()->create(['name' => 'Child blog', 'parent_id' => $root->id, 'sort' => 2, 'count_articles' => 2]);
        Blog::factory()->create(['name' => 'Grandchild blog', 'parent_id' => $child->id, 'sort' => 3, 'count_articles' => 4]);

        $this->actingAs($boss)
            ->get(route('admin.blogs.index'))
            ->assertOk()
            ->assertSee('Grandchild blog')
            // Счётчик корня складывает всю ветку: 1 + 2 + 4
            ->assertSee('>7</span>', false);
    }

    public function testIndexHidesSortFormFromAdminBelowBoss(): void
    {
        $admin = User::factory()->admin()->create();

        Blog::factory()->create(['name' => 'Root blog', 'sort' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.blogs.index'))
            ->assertOk()
            ->assertSee('Root blog')
            ->assertDontSee('data-sortable-tree', false);
    }
}
