<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;
use Tests\ModuleTestCase;

class ArticleWriteApiTest extends ModuleTestCase
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
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
        $this->category = Blog::query()->create(['parent_id' => 0, 'name' => 'Технологии', 'sort' => 1]);
    }

    public function testStoreRequiresToken(): void
    {
        $this->postJson('/api/articles', $this->payload())->assertStatus(400);
    }

    public function testStoreCreatesArticle(): void
    {
        $response = $this->postJson('/api/articles', $this->payload(), $this->headers());

        $response->assertStatus(201)
            ->assertJsonPath('article.title', 'Статья про телефоны')
            ->assertJsonPath('article.category.id', $this->category->id)
            ->assertJsonCount(2, 'article.tags');

        $this->assertDatabaseHas('articles', [
            'title'   => 'Статья про телефоны',
            'user_id' => $this->user->id,
            'active'  => 1,
        ]);
    }

    public function testStoreAttachesPendingFiles(): void
    {
        $file = File::query()->create([
            'relate_id'   => 0,
            'relate_type' => Article::$morphName,
            'path'        => '/uploads/articles/screen.jpg',
            'name'        => 'screen.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);

        $id = $this->postJson('/api/articles', $this->payload(), $this->headers())->json('article.id');

        $this->assertSame($id, $file->fresh()->relate_id);
    }

    public function testDraftIsNotPublished(): void
    {
        $id = $this->postJson('/api/articles', $this->payload() + ['draft' => true], $this->headers())
            ->assertStatus(201)
            ->json('article.id');

        $this->assertDatabaseHas('articles', ['id' => $id, 'draft' => 1, 'active' => 0]);
    }

    public function testModerationHoldsArticle(): void
    {
        $this->overrideSetting('article_moderation', 1);

        $id = $this->postJson('/api/articles', $this->payload(), $this->headers())
            ->assertStatus(201)
            ->json('article.id');

        // Статья ждёт проверки: на сайте её ещё не видно
        $this->assertDatabaseHas('articles', ['id' => $id, 'active' => 0]);
    }

    public function testTagsAreRequired(): void
    {
        $payload = $this->payload();
        unset($payload['tags']);

        $this->postJson('/api/articles', $payload, $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('tags');
    }

    public function testStoreIsBlockedWhenSectionClosed(): void
    {
        $this->overrideSetting('blog_create', 0);

        $this->postJson('/api/articles', $this->payload(), $this->headers())->assertStatus(422);
    }

    public function testClosedCategoryIsRejected(): void
    {
        $this->category->update(['closed' => 1]);

        $this->postJson('/api/articles', $this->payload(), $this->headers())->assertStatus(422);
    }

    public function testUpdateOwnArticle(): void
    {
        $article = $this->createArticle($this->user->id);

        $this->patchJson('/api/articles/' . $article->id, [
            'category_id' => $this->category->id,
            'title'       => 'Изменённая статья',
            'text'        => 'Изменённый текст статьи',
            'tags'        => ['телефоны'],
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('article.title', 'Изменённая статья')
            ->assertJsonCount(1, 'article.tags');
    }

    public function testPublishTurnsDraftIntoArticle(): void
    {
        $article = $this->createArticle($this->user->id);
        $article->update(['draft' => 1, 'active' => 0]);

        $this->patchJson('/api/articles/' . $article->id, [
            'category_id' => $this->category->id,
            'title'       => 'Статья про телефоны',
            'text'        => 'Текст статьи про телефоны',
            'tags'        => ['телефоны'],
            'publish'     => true,
        ], $this->headers())->assertOk();

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'draft' => 0, 'active' => 1]);
    }

    public function testForeignArticleIsProtected(): void
    {
        $article = $this->createArticle(User::factory()->create()->id);

        $this->patchJson('/api/articles/' . $article->id, [
            'category_id' => $this->category->id,
            'title'       => 'Изменённая статья',
            'text'        => 'Изменённый текст статьи',
            'tags'        => ['телефоны'],
        ], $this->headers())->assertStatus(403);
    }

    private function payload(): array
    {
        return [
            'category_id' => $this->category->id,
            'title'       => 'Статья про телефоны',
            'text'        => 'Текст статьи про телефоны',
            'tags'        => ['телефоны', 'обзоры'],
        ];
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }

    private function createArticle(int $userId): Article
    {
        return Article::query()->create([
            'category_id' => $this->category->id,
            'user_id'     => $userId,
            'title'       => 'Статья про телефоны',
            'slug'        => 'statya-pro-telefony',
            'text'        => 'Текст статьи про телефоны',
            'active'      => 1,
            'created_at'  => now(),
        ]);
    }
}
