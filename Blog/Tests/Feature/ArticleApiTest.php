<?php

namespace Modules\Blog\Tests\Feature;

use App\Classes\Registry;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;
use Modules\Blog\Models\Tag;
use Tests\ModuleTestCase;

class ArticleApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Blog';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Article::$morphName => Article::class]);

        // Типы регистрирует ModuleServiceProvider, в тестах модуль поднимается вручную
        Registry::ratingType(Article::$morphName);

        $this->user = User::factory()->create();
    }

    public function testViewIsAvailableForGuests(): void
    {
        $article = $this->createArticle();

        $response = $this->getJson('/api/articles/' . $article->slug);

        $response->assertOk();
        $response->assertJsonPath('article.id', $article->id);
        $response->assertJsonPath('article.title', 'Test article');
        $response->assertJsonPath('article.url', $article->getViewUrl());
        $response->assertJsonPath('article.user.login', $this->user->login);
        $response->assertJsonPath('article.breadcrumbs.0.title', __('blog::blogs.blogs'));
        $response->assertJsonPath('article.breadcrumbs.1.title', 'Test category');
        // Категория записи приходит объектом, как раздел у темы форума
        $response->assertJsonPath('article.category_id', $article->category_id);
        $response->assertJsonPath('article.category.name', 'Test category');
        $response->assertJsonPath('article.category.url', route('blogs.blog', ['id' => $article->category_id]));
        $response->assertJsonPath('article.category.parent', null);
        $response->assertJsonPath('article.vote.value', null);
        $response->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function testViewAcceptsIdInsteadOfSlug(): void
    {
        $article = $this->createArticle();

        $this->getJson('/api/articles/' . $article->id)
            ->assertOk()
            ->assertJsonPath('article.id', $article->id);
    }

    public function testViewReturnsTags(): void
    {
        $article = $this->createArticle();
        $tag = Tag::query()->create(['name' => 'laravel']);
        $article->tags()->attach($tag->id, ['sort' => 1]);

        $response = $this->getJson('/api/articles/' . $article->slug);

        $response->assertOk();
        $response->assertJsonPath('article.tags.0.name', 'laravel');
        $response->assertJsonPath('article.tags.0.url', route('blogs.tag', ['tag' => 'laravel']));
    }

    public function testViewReturnsCommentsPaginated(): void
    {
        $article = $this->createArticle();

        $first = $this->addComment($article, 'First comment');
        $this->addComment($article, 'Second comment', $first->id);

        $response = $this->getJson('/api/articles/' . $article->slug . '?per_page=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.parent_id', null);

        $this->getJson('/api/articles/' . $article->slug . '?per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.parent_id', $first->id);
    }

    public function testViewReturnsVoteOfCurrentUser(): void
    {
        $article = $this->createArticle();
        $voter = User::factory()->create(['apikey' => Str::random(32)]);

        $this->postJson('/api/rating', [
            'type' => Article::$morphName,
            'id'   => $article->id,
            'vote' => '+',
        ], ['Authorization' => 'Bearer ' . $voter->apikey])->assertOk();

        $this->getJson('/api/articles/' . $article->slug, ['Authorization' => 'Bearer ' . $voter->apikey])
            ->assertOk()
            ->assertJsonPath('article.vote.value', '+')
            ->assertJsonPath('article.vote.own', false)
            ->assertJsonPath('article.rating', 1);
    }

    public function testIndexFiltersByCategory(): void
    {
        $article = $this->createArticle();

        $other = Blog::query()->create(['name' => 'Other category']);
        Article::query()->create([
            'category_id' => $other->id,
            'user_id'     => $this->user->id,
            'title'       => 'Other article',
            'slug'        => 'other-article',
            'text'        => 'Other article text',
            'active'      => true,
            'created_at'  => now(),
        ]);

        $this->getJson('/api/articles')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/articles?category_id=' . $article->category_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Test article');
    }

    public function testCategoriesReturnTree(): void
    {
        $parent = Blog::query()->create(['name' => 'Parent category']);
        Blog::query()->create(['name' => 'Child category', 'parent_id' => $parent->id]);

        $this->getJson('/api/blogs')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Parent category')
            ->assertJsonPath('data.0.url', route('blogs.blog', ['id' => $parent->id]))
            ->assertJsonPath('data.0.children.0.name', 'Child category');
    }

    public function testViewNotFound(): void
    {
        $this->getJson('/api/articles/100-missing')->assertStatus(404);
    }

    private function createArticle(): Article
    {
        $blog = Blog::query()->create(['name' => 'Test category']);

        return Article::query()->create([
            'category_id' => $blog->id,
            'user_id'     => $this->user->id,
            'title'       => 'Test article',
            'slug'        => 'test-article',
            'text'        => 'Test article text',
            'active'      => true,
            'created_at'  => now(),
        ]);
    }

    private function addComment(Article $article, string $text, ?int $parentId = null): Comment
    {
        return Comment::query()->create([
            'relate_type' => Article::$morphName,
            'relate_id'   => $article->id,
            'parent_id'   => $parentId,
            'text'        => $text,
            'user_id'     => $this->user->id,
            'created_at'  => now(),
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
        ]);
    }
}
