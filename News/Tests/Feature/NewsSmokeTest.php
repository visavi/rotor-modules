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

        $this->get(route('news.all-comments'))
            ->assertOk()
            ->assertSee('href="' . route('news.view', ['id' => $news->id]) . '#comment_' . $comment->id . '"', false);
    }
}
