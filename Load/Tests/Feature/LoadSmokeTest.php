<?php

namespace Modules\Load\Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;
use Tests\ModuleTestCase;

class LoadSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Load';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Down::$morphName => Down::class]);

        $this->user = User::factory()->create();
    }

    public function testIndex(): void
    {
        $this->get(route('loads.index'))->assertOk();
    }

    public function testCategory(): void
    {
        $load = Load::factory()->create(['name' => 'Test category']);

        $this->get(route('loads.load', ['id' => $load->id]))->assertOk();
    }

    public function testNestedCategoryBreadcrumbs(): void
    {
        // Одна категория защиту от ленивой загрузки не включает: нужен набор, как в живом дереве
        $root = Load::factory()->create(['name' => 'Корневой раздел']);
        $child = Load::factory()->create(['name' => 'Подраздел', 'parent_id' => $root->id]);

        $this->get(route('loads.load', ['id' => $child->id]))
            ->assertOk()
            ->assertSee('Корневой раздел');
    }

    public function testDown(): void
    {
        $load = Load::factory()->create(['name' => 'Test category']);

        $down = Down::factory()->create([
            'category_id' => $load->id,
            'title'       => 'Test down',
            'text'        => 'Test down text',
            'user_id'     => $this->user->id,
            'active'      => true,
            'created_at'  => now()->timestamp,
        ]);

        $this->get($down->getViewUrl())->assertOk();
    }

    public function testNewCommentsLinkToComment(): void
    {
        // Ссылка ведёт к самому комментарию, а не на начало страницы через ?cid=
        $load = Load::factory()->create(['name' => 'Test category']);

        $down = Down::factory()->create([
            'category_id' => $load->id,
            'title'       => 'Test down',
            'text'        => 'Test down text',
            'user_id'     => $this->user->id,
            'active'      => true,
            'created_at'  => now(),
        ]);

        $comment = Comment::factory()->create([
            'relate_type' => Down::$morphName,
            'relate_id'   => $down->id,
            'text'        => 'Test comment',
            'user_id'     => $this->user->id,
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
            'created_at'  => now(),
        ]);

        $this->get(route('downs.new-comments'))
            ->assertOk()
            ->assertSee('href="' . route('downs.view', ['id' => $down->id]) . '#comment_' . $comment->id . '"', false);
    }

    public function testUserPagesAreClosedForGuests(): void
    {
        // Страницы пользователя только авторизованным — гостю 403
        $routes = ['downs.active-files', 'downs.active-comments'];

        foreach ($routes as $route) {
            $this->get(route($route, ['user' => $this->user->login]))->assertForbidden();
        }

        $this->actingAs($this->user);

        foreach ($routes as $route) {
            $this->get(route($route, ['user' => $this->user->login]))->assertOk();
        }
    }

    public function testArrayUserFallsBackToOwnPages(): void
    {
        // ?user[]= давал TypeError в getUserByLogin — 500 вместо своих страниц
        $this->actingAs($this->user);

        foreach (['downs.active-files', 'downs.active-comments'] as $route) {
            $this->get(route($route) . '?user[]=1')->assertOk()->assertSee($this->user->getName());
        }
    }

    public function testRssSkipsUnverifiedDowns(): void
    {
        $load = Load::factory()->create(['name' => 'Test category']);

        // Два проверенных: с одной записью защита от ленивой загрузки не включается
        foreach (['Проверенный файл' => true, 'Второй файл' => true, 'Ждёт проверки' => false] as $title => $active) {
            Down::factory()->create([
                'category_id' => $load->id,
                'title'       => $title,
                'text'        => 'Текст',
                'user_id'     => $this->user->id,
                'active'      => $active,
                'created_at'  => now(),
            ]);
        }

        $this->get(route('loads.rss'))
            ->assertOk()
            ->assertSee('Проверенный файл')
            ->assertDontSee('Ждёт проверки');
    }
}
