<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;
use Tests\ModuleTestCase;

class ArticleFormTest extends ModuleTestCase
{
    protected string $moduleName = 'Blog';

    private User $user;

    private Blog $category;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Article::$morphName => Article::class]);

        $this->overrideSetting('blog_create', 1);
        $this->overrideSetting('article_moderation', 0);
        $this->overrideSetting('blog_title_min', 5);
        $this->overrideSetting('blog_title_max', 50);
        $this->overrideSetting('blog_text_min', 10);
        $this->overrideSetting('blog_text_max', 5000);
        $this->overrideSetting('blog_tag_min', 2);
        $this->overrideSetting('blog_tag_max', 20);
        $this->overrideSetting('blog_category_min', 3);
        $this->overrideSetting('blog_category_max', 50);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create();
        $this->category = Blog::factory()->create(['parent_id' => 0, 'name' => 'Технологии', 'sort' => 1]);
    }

    public function testArticleIsCreatedWithTags(): void
    {
        $this->actingAs($this->user)
            ->get('/blogs/create?cid=' . $this->category->id)
            ->assertOk()
            ->assertSee('name="category_id"', false);

        $this->actingAs($this->user)
            ->post('/blogs/create', ['tags' => ['Обзоры', '', 'Обзоры', 'телефоны']] + $this->payload())
            ->assertSessionHas('success');

        $article = Article::query()->where('title', 'Статья про телефоны')->firstOrFail();

        $this->assertTrue((bool) $article->active);
        $this->assertSame(['обзоры', 'телефоны'], $article->tags->pluck('name')->all());
    }

    public function testDraftIsNotPublished(): void
    {
        $this->actingAs($this->user)
            ->post('/blogs/create', ['action' => 'draft'] + $this->payload())
            ->assertSessionHas('success', __('blog::blogs.article_success_drafts'));

        $article = Article::query()->firstOrFail();

        $this->assertTrue((bool) $article->draft);
        $this->assertFalse((bool) $article->active);
    }

    public function testFormErrorsAreShownUnderTheirField(): void
    {
        $this->category->update(['closed' => 1]);

        $this->actingAs($this->user)
            ->post('/blogs/create', ['text' => '<p><b>Коротко</b></p>', 'tags' => ['x']] + $this->payload())
            ->assertSessionHasErrors(['category_id', 'text', 'tags.0']);

        $this->assertDatabaseCount('articles', 0);
    }

    public function testFloodErrorIsShownUnderText(): void
    {
        $this->overrideSetting('floodstime', 60);

        $this->actingAs($this->user)->post('/blogs/create', $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($this->user)->post('/blogs/create', $this->payload())->assertSessionHasErrors('text');

        $this->assertDatabaseCount('articles', 1);
    }

    public function testDelayWithoutDateIsRejected(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);

        // Раньше статья молча сохранялась неактивной без даты и не публиковалась никогда
        $this->actingAs($admin)
            ->post('/blogs/create', ['delay' => 1, 'published_at' => ''] + $this->payload())
            ->assertSessionHasErrors('published_at');

        $this->assertDatabaseCount('articles', 0);
    }

    public function testDelayedPublication(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);
        $date = now()->addDay()->startOfMinute();

        $this->actingAs($admin)
            ->post('/blogs/create', ['delay' => 1, 'published_at' => $date->format('Y-m-d\TH:i')] + $this->payload())
            ->assertSessionHasNoErrors();

        $article = Article::query()->firstOrFail();

        $this->assertFalse((bool) $article->active);
        $this->assertSame($date->format('Y-m-d H:i'), $article->published_at->format('Y-m-d H:i'));
    }

    public function testDateWithoutDelayIsIgnored(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);

        // Снятый флажок прячет поле, но браузер всё равно отправляет дату.
        // Сам флажок снятым не приходит — ноль за него шлёт скрытое поле формы
        $this->actingAs($admin)
            ->get('/blogs/create')
            ->assertSee('<input type="hidden" name="delay" value="0">', false);

        $this->actingAs($admin)
            ->post('/blogs/create', ['delay' => 0, 'published_at' => now()->subDay()->format('Y-m-d\TH:i')] + $this->payload())
            ->assertSessionHasNoErrors();

        $article = Article::query()->firstOrFail();

        $this->assertTrue((bool) $article->active);
        $this->assertNull($article->published_at);
    }

    public function testGuestCannotCreate(): void
    {
        $this->get('/blogs/create')->assertForbidden();
        $this->post('/blogs/create', $this->payload())->assertForbidden();
    }

    public function testClosedCreationHidesAddButton(): void
    {
        $this->overrideSetting('blog_create', 0);
        $admin = User::factory()->create(['level' => User::ADMIN]);

        foreach (['/blogs', '/blogs/' . $this->category->id] as $url) {
            $this->actingAs($this->user)->get($url)->assertOk()->assertDontSee(route('blogs.create'));
            $this->actingAs($admin)->get($url)->assertOk()->assertSee(route('blogs.create'));
        }
    }

    public function testOwnDraftIsPublishedFromEditForm(): void
    {
        $article = $this->createArticle(['draft' => 1, 'active' => 0]);
        $other = Blog::factory()->create(['parent_id' => 0, 'name' => 'Обзоры', 'sort' => 2]);

        $this->actingAs($this->user)
            ->get('/articles/' . $article->id . '/edit')
            ->assertOk();

        $this->actingAs($this->user)
            ->post('/articles/' . $article->id . '/edit', ['action' => 'publish', 'category_id' => $other->id] + $this->payload())
            ->assertSessionHas('success', __('blog::blogs.article_success_created'));

        $article->refresh();

        $this->assertSame($other->id, $article->category_id);
        $this->assertFalse((bool) $article->draft);
        $this->assertTrue((bool) $article->active);
    }

    public function testForeignArticleIsNotEdited(): void
    {
        $article = $this->createArticle(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user)
            ->post('/articles/' . $article->id . '/edit', $this->payload())
            ->assertSee(__('blog::blogs.article_not_author'));

        $this->assertSame('Старая статья', $article->fresh()->title);
    }

    public function testAdminEditsArticle(): void
    {
        $article = $this->createArticle();

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->post('/admin/articles/' . $article->id . '/edit', ['title' => 'Правка администрации'] + $this->payload())
            ->assertRedirect(route('articles.view', ['slug' => $article->fresh()->slug]));

        $this->assertSame('Правка администрации', $article->fresh()->title);
    }

    public function testBossManagesCategories(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->post('/admin/blogs/create', ['name' => 'Новости'])
            ->assertForbidden();

        $this->actingAs($boss)
            ->post('/admin/blogs/create', ['name' => 'Ок'])
            ->assertSessionHasErrors('name');

        $this->actingAs($boss)
            ->post('/admin/blogs/create', ['name' => 'Новости'])
            ->assertSessionHas('success');

        $news = Blog::query()->where('name', 'Новости')->firstOrFail();

        // Ошибка имени раньше уходила под ключ title, а поле называется name
        $this->actingAs($boss)
            ->post('/admin/blogs/' . $news->id . '/edit', ['name' => 'Ок', 'parent' => 0])
            ->assertSessionHasErrors('name');

        $this->actingAs($boss)
            ->post('/admin/blogs/' . $news->id . '/edit', ['name' => 'Новости', 'parent' => $news->id])
            ->assertSessionHasErrors('parent');

        $this->actingAs($boss)
            ->post('/admin/blogs/' . $news->id . '/edit', ['name' => 'Новости сайта', 'parent' => $this->category->id, 'closed' => 1])
            ->assertRedirect(route('admin.blogs.index'));

        $news->refresh();
        $this->assertSame('Новости сайта', $news->name);
        $this->assertSame($this->category->id, $news->parent_id);

        // Раздел со статьями не удаляется
        $this->createArticle(['category_id' => $news->id]);

        $this->actingAs($boss)
            ->delete('/admin/blogs/' . $news->id . '/delete')
            ->assertSessionHasErrors();

        $this->assertNotNull($news->fresh());
    }

    public function testPublishingDraftKeepsModeration(): void
    {
        $this->overrideSetting('article_moderation', 1);
        $draft = $this->createArticle(['draft' => 1, 'active' => 0]);

        // Раньше кнопка «опубликовать» делала черновик активным в обход модерации
        $this->actingAs($this->user)
            ->post('/articles/' . $draft->id . '/edit', ['action' => 'publish'] + $this->payload())
            ->assertSessionHasNoErrors();

        $draft->refresh();
        $this->assertFalse((bool) $draft->draft);
        $this->assertFalse($draft->active);
    }

    private function payload(): array
    {
        return [
            'category_id' => $this->category->id,
            'title'       => 'Статья про телефоны',
            'text'        => '<p>Текст статьи про телефоны</p>',
            'tags'        => ['телефоны', 'обзоры'],
        ];
    }

    private function createArticle(array $attributes = []): Article
    {
        return Article::factory()->create($attributes + [
            'category_id' => $this->category->id,
            'user_id'     => $this->user->id,
            'title'       => 'Старая статья',
            'slug'        => 'staraya-statya',
            'text'        => 'Текст старой статьи',
            'active'      => 1,
            'created_at'  => now(),
        ]);
    }
}
