<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;
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
            'created_at'  => SITETIME,
        ]);

        $this->get(route('articles.view', ['slug' => $article->slug]))->assertOk();
    }
}
