<?php

namespace Modules\News\Tests\Feature;

use App\Classes\Registry;
use App\Models\Comment;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\News\Models\News;
use Tests\ModuleTestCase;

class FeedApiTest extends ModuleTestCase
{
    protected string $moduleName = 'News';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Комментарий виден в ленте, только если тип записи зарегистрирован
        Relation::morphMap([News::$morphName => News::class]);
        Registry::label(News::$morphName, __('news::news.news'));

        $this->overrideSetting('feed_comments_show', 1);
        $this->overrideSetting('feed_per_page', 20);
        $this->overrideSetting('feed_cache_time', 0);

        $this->user = User::factory()->create();
    }

    public function testFeedReturnsCommentWithRelate(): void
    {
        [$news, $comment] = $this->createCommentInFeed();

        $response = $this->get('/api/feed');

        $response->assertOk();
        $response->assertJsonPath('data.0.type', Comment::$morphName);
        $response->assertJsonPath('data.0.id', $comment->id);
        $response->assertJsonPath('data.0.relate.type', News::$morphName);
        $response->assertJsonPath('data.0.relate.id', $news->id);
        $response->assertJsonPath('data.0.relate.title', 'Test news');
        $response->assertJsonPath('data.0.relate.url', $news->getViewUrl());
    }

    public function testFeedReturnsCommentBreadcrumbs(): void
    {
        [$news] = $this->createCommentInFeed();

        $response = $this->get('/api/feed');

        $response->assertOk();
        $response->assertJsonPath('data.0.breadcrumbs.0.title', __('news::news.news'));
        $response->assertJsonPath('data.0.breadcrumbs.0.url', url(News::$morphName));
        $response->assertJsonPath('data.0.breadcrumbs.1.title', 'Test news');
        $response->assertJsonPath('data.0.breadcrumbs.1.url', $news->getViewUrl());
    }

    public function testFeedSplitsMediaAndFiles(): void
    {
        [, $comment] = $this->createCommentInFeed();

        $this->attachFile($comment, 'photo.jpg', 'jpg', 'image/jpeg');
        $this->attachFile($comment, 'manual.pdf', 'pdf', 'application/pdf');

        $response = $this->get('/api/feed');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.0.media');
        $response->assertJsonCount(1, 'data.0.files');
        $response->assertJsonPath('data.0.media.0.name', 'photo.jpg');
        $response->assertJsonPath('data.0.files.0.name', 'manual.pdf');
    }

    public function testFeedAuthorIsCompact(): void
    {
        $this->createCommentInFeed();

        $response = $this->get('/api/feed');

        $response->assertOk();
        $response->assertJsonPath('data.0.user.login', $this->user->login);
        // Профиль целиком в ленту не отдаём
        $response->assertJsonMissingPath('data.0.user.money');
        $response->assertJsonMissingPath('data.0.user.point');
        $response->assertJsonMissingPath('data.0.user.birthday');
    }

    private function attachFile(Comment $comment, string $name, string $extension, string $mimeType): void
    {
        File::query()->create([
            'relate_id'   => $comment->id,
            'relate_type' => $comment->getMorphClass(),
            'path'        => '/uploads/comments/' . $name,
            'name'        => $name,
            'size'        => 1024,
            'extension'   => $extension,
            'mime_type'   => $mimeType,
            'user_id'     => $this->user->id,
        ]);
    }

    /**
     * @return array{0: News, 1: Comment}
     */
    private function createCommentInFeed(): array
    {
        $news = News::query()->create([
            'title'      => 'Test news',
            'text'       => 'Test news text',
            'user_id'    => $this->user->id,
            'created_at' => now(),
        ]);

        $comment = Comment::query()->create([
            'relate_type' => News::$morphName,
            'relate_id'   => $news->id,
            'text'        => 'Test comment text',
            'user_id'     => $this->user->id,
            'created_at'  => now(),
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
        ]);

        return [$news, $comment];
    }
}
