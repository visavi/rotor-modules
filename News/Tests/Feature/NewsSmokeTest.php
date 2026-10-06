<?php

namespace Modules\News\Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\News\Models\News;
use Tests\ModuleTestCase;

class NewsSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'News';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([News::$morphName => News::class]);

        $this->user = User::factory()->create();
    }

    public function testNewsAcceptsDocuments(): void
    {
        $this->overrideSetting('file_extensions', 'pdf,jpg');
        $this->overrideSetting('media_extensions', 'jpg,mp4');

        // Новости принимают файлы, а не только медиа. Без регистрации тип
        // тоже получил бы file_extensions — проверяем её саму
        $this->assertContains(News::$morphName, FileService::fileTypes());
        $this->assertSame(['pdf', 'jpg'], FileService::extensions(News::$morphName));
    }

    public function testIndex(): void
    {
        $this->get(route('news.index'))->assertOk();
    }

    public function testView(): void
    {
        $news = News::query()->create([
            'title'      => 'Test news',
            'text'       => 'Test news text',
            'user_id'    => $this->user->id,
            'created_at' => now()->timestamp,
        ]);

        $this->get($news->getViewUrl())->assertOk();
    }

    public function testAllCommentsLinkToComment(): void
    {
        // Ссылка ведёт к самому комментарию, а не на начало страницы через ?cid=
        $news = News::query()->create([
            'title'      => 'Test news',
            'text'       => 'Test news text',
            'user_id'    => $this->user->id,
            'created_at' => now(),
        ]);

        $comment = Comment::query()->create([
            'relate_type' => News::$morphName,
            'relate_id'   => $news->id,
            'text'        => 'Test comment',
            'user_id'     => $this->user->id,
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
            'created_at'  => now(),
        ]);

        $this->get(route('news.new-comments'))
            ->assertOk()
            ->assertSee('href="' . route('news.view', ['id' => $news->id]) . '#comment_' . $comment->id . '"', false);
    }

    public function testOldCommentsUrlRedirects(): void
    {
        $this->assertSame(url('/news/comments'), route('news.new-comments'));
        $this->get('/news/allcomments')->assertRedirect('/news/comments');
    }

    public function testApiCommentFeedOfSection(): void
    {
        // Лента раздела: только его комментарии, с записью для перехода
        $news = News::query()->create([
            'title'      => 'Test news',
            'text'       => 'Test news text',
            'user_id'    => $this->user->id,
            'created_at' => now(),
        ]);

        $comment = Comment::query()->create([
            'relate_type' => News::$morphName,
            'relate_id'   => $news->id,
            'text'        => 'Test comment',
            'user_id'     => $this->user->id,
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
            'created_at'  => now(),
        ]);

        Comment::query()->create([
            'relate_type' => Comment::$morphName,
            'relate_id'   => 1,
            'text'        => 'Other comment',
            'user_id'     => $this->user->id,
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
            'created_at'  => now(),
        ]);

        $this->getJson('/api/comments?type=' . News::$morphName)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $comment->id)
            ->assertJsonPath('data.0.relate.type', News::$morphName)
            ->assertJsonPath('data.0.relate.id', $news->id)
            ->assertJsonPath('data.0.relate.title', 'Test news');
    }
}
