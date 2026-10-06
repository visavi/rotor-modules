<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;
use Modules\Blog\Models\Tag;
use Tests\ModuleTestCase;

class BlogSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Blog';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Article::$morphName => Article::class]);

        $this->user = User::factory()->create();
    }

    public function testIndex(): void
    {
        $this->get(route('blogs.index'))->assertOk();
    }

    public function testCategory(): void
    {
        $blog = Blog::query()->create(['name' => 'Test category']);

        $this->get(route('blogs.blog', ['id' => $blog->id]))->assertOk();
    }

    public function testArticle(): void
    {
        $blog = Blog::query()->create(['name' => 'Test category']);

        $article = Article::query()->create([
            'category_id' => $blog->id,
            'user_id'     => $this->user->id,
            'title'       => 'Test article',
            'slug'        => 'test-article',
            'text'        => 'Test article text',
            'created_at'  => now()->timestamp,
        ]);

        $this->get($article->getViewUrl())->assertOk();
    }

    public function testNewComments(): void
    {
        // Адрес как в других разделах; /articles/comments не принимается за статью
        $this->get(route('articles.new-comments'))->assertOk();
        $this->assertSame(url('/articles/comments'), route('articles.new-comments'));

        $this->get('/articles/new/comments')->assertRedirect('/articles/comments');
    }

    public function testApiCommentFeedHidesInactiveArticle(): void
    {
        // Заголовок неопубликованной статьи в ленту не попадает
        $blog = Blog::query()->create(['name' => 'Test category']);

        foreach ([true, false] as $active) {
            $article = Article::query()->create([
                'category_id' => $blog->id,
                'user_id'     => $this->user->id,
                'title'       => $active ? 'Public article' : 'Draft article',
                'slug'        => 'article',
                'text'        => 'Text',
                'active'      => $active,
                'created_at'  => now(),
            ]);

            Comment::query()->create([
                'relate_type' => Article::$morphName,
                'relate_id'   => $article->id,
                'text'        => 'Comment',
                'user_id'     => $this->user->id,
                'ip'          => '127.0.0.1',
                'brow'        => 'test',
                'created_at'  => now()->addSeconds((int) $active),
            ]);
        }

        $this->getJson('/api/comments?type=' . Article::$morphName)
            ->assertOk()
            ->assertJsonPath('data.0.relate.title', 'Public article')
            ->assertJsonPath('data.1.relate', null);
    }

    public function testTagCloudOrderIsStableBetweenViews(): void
    {
        // Облако перемешано, но не скачет при обновлении страницы
        $blog = Blog::query()->create(['name' => 'Test category']);
        $article = Article::query()->create([
            'category_id' => $blog->id,
            'user_id'     => $this->user->id,
            'title'       => 'Test article',
            'slug'        => 'test-article',
            'text'        => 'Text',
            'active'      => true,
            'created_at'  => now(),
        ]);

        foreach (range(1, 10) as $i) {
            $article->tags()->attach(Tag::query()->create(['name' => 'tag' . $i])->id, ['sort' => $i]);
        }

        $order = fn () => preg_match_all('~/blogs/tags/(tag\d+)~', $this->get(route('blogs.tags'))->assertOk()->getContent(), $m) ? $m[1] : [];

        $first = $order();

        $this->assertCount(10, $first);
        $this->assertSame($first, $order());
    }

    public function testTagPageHidesDrafts(): void
    {
        $blog = Blog::query()->create(['name' => 'Test category']);
        $tag = Tag::query()->create(['name' => 'rotor']);

        foreach (['Public article' => true, 'Draft article' => false] as $title => $active) {
            Article::query()->create([
                'category_id' => $blog->id,
                'user_id'     => $this->user->id,
                'title'       => $title,
                'slug'        => 'article',
                'text'        => 'Text',
                'active'      => $active,
                'created_at'  => now(),
            ])->tags()->attach($tag->id, ['sort' => 0]);
        }

        $this->get(route('blogs.tag', ['tag' => 'rotor']))
            ->assertOk()
            ->assertSee('Public article')
            ->assertDontSee('Draft article');
    }

    public function testPublishingClearsTagCloud(): void
    {
        // Облако считает только опубликованные — публикация должна его пересобрать
        Cache::put('tagCloud', ['stale' => 1], 3600);

        $article = Article::query()->create([
            'category_id' => Blog::query()->create(['name' => 'Test category'])->id,
            'user_id'     => $this->user->id,
            'title'       => 'Draft',
            'slug'        => 'draft',
            'text'        => 'Text',
            'active'      => false,
            'created_at'  => now(),
        ]);

        $article->update(['active' => true]);

        $this->assertFalse(Cache::has('tagCloud'));
    }
}
