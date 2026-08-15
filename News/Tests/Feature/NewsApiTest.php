<?php

namespace Modules\News\Tests\Feature;

use App\Classes\Registry;
use App\Models\Comment;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\News\Models\News;
use Tests\ModuleTestCase;

class NewsApiTest extends ModuleTestCase
{
    protected string $moduleName = 'News';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([News::$morphName => News::class]);

        // Типы регистрирует ModuleServiceProvider, в тестах модуль поднимается вручную
        Registry::ratingType(News::$morphName);
        Registry::label(News::$morphName, __('news::news.news'));

        $this->user = User::factory()->create();
    }

    public function testViewIsAvailableForGuests(): void
    {
        $news = $this->createNews();

        $response = $this->getJson('/api/news/' . $news->id);

        $response->assertOk();
        $response->assertJsonPath('news.id', $news->id);
        $response->assertJsonPath('news.title', 'Test news');
        $response->assertJsonPath('news.url', $news->getViewUrl());
        $response->assertJsonPath('news.user.login', $this->user->login);
        $response->assertJsonPath('news.breadcrumbs.0.title', __('news::news.news'));
        $response->assertJsonPath('news.vote.value', null);
        $response->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function testViewSplitsMediaAndFiles(): void
    {
        $news = $this->createNews();

        $this->attachFile($news, 'photo.jpg', 'jpg', 'image/jpeg');
        $this->attachFile($news, 'manual.pdf', 'pdf', 'application/pdf');

        $response = $this->getJson('/api/news/' . $news->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'news.media');
        $response->assertJsonCount(1, 'news.files');
        $response->assertJsonPath('news.media.0.name', 'photo.jpg');
        $response->assertJsonPath('news.files.0.name', 'manual.pdf');
    }

    public function testViewReturnsCommentsPaginated(): void
    {
        $news = $this->createNews();

        $first = $this->addComment($news, 'First comment');
        $this->addComment($news, 'Second comment', $first->id);

        $response = $this->getJson('/api/news/' . $news->id . '?per_page=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.parent_id', null);

        $this->getJson('/api/news/' . $news->id . '?per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.parent_id', $first->id);
    }

    public function testViewReturnsVoteOfCurrentUser(): void
    {
        $news = $this->createNews();
        $voter = User::factory()->create(['apikey' => Str::random(32)]);

        $this->postJson('/api/rating', [
            'type' => News::$morphName,
            'id'   => $news->id,
            'vote' => '+',
        ], ['Authorization' => 'Bearer ' . $voter->apikey])->assertOk();

        $this->getJson('/api/news/' . $news->id, ['Authorization' => 'Bearer ' . $voter->apikey])
            ->assertOk()
            ->assertJsonPath('news.vote.value', '+')
            ->assertJsonPath('news.vote.own', false)
            ->assertJsonPath('news.rating', 1);
    }

    public function testIndexReturnsList(): void
    {
        $this->createNews();
        $this->createNews();

        $response = $this->getJson('/api/news?per_page=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.title', 'Test news');
    }

    public function testViewNotFound(): void
    {
        $this->getJson('/api/news/100')->assertStatus(404);
    }

    private function createNews(): News
    {
        return News::query()->create([
            'title'      => 'Test news',
            'text'       => 'Test news text',
            'user_id'    => $this->user->id,
            'created_at' => now(),
        ]);
    }

    private function attachFile(News $news, string $name, string $extension, string $mimeType): void
    {
        File::query()->create([
            'relate_id'   => $news->id,
            'relate_type' => News::$morphName,
            'path'        => '/uploads/news/' . $name,
            'name'        => $name,
            'size'        => 1024,
            'extension'   => $extension,
            'mime_type'   => $mimeType,
            'user_id'     => $this->user->id,
        ]);
    }

    private function addComment(News $news, string $text, ?int $parentId = null): Comment
    {
        return Comment::query()->create([
            'relate_type' => News::$morphName,
            'relate_id'   => $news->id,
            'parent_id'   => $parentId,
            'text'        => $text,
            'user_id'     => $this->user->id,
            'created_at'  => now(),
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
        ]);
    }
}
