<?php

namespace Modules\News\Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\News\Models\News;
use Tests\ModuleTestCase;

/**
 * Комментарии с сайта: HandlesComments::storeComment и правка через /comments/{id}
 */
class NewsCommentTest extends ModuleTestCase
{
    protected string $moduleName = 'News';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([News::$morphName => News::class]);

        $this->overrideSetting('comment_text_min', 5);
        $this->overrideSetting('comment_text_max', 100);

        $this->user = User::factory()->create();
    }

    private function makeNews(array $attributes = []): News
    {
        return News::factory()->create(array_merge([
            'title'      => 'Test news',
            'text'       => 'Test news text',
            'user_id'    => $this->user->id,
            'created_at' => now(),
        ], $attributes));
    }

    private function makeComment(News $news): Comment
    {
        return Comment::factory()->create([
            'relate_type' => News::$morphName,
            'relate_id'   => $news->id,
            'text'        => 'Исходный комментарий',
            'user_id'     => $this->user->id,
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
            'created_at'  => now(),
        ]);
    }

    public function testStoreComment(): void
    {
        $news = $this->makeNews();

        $response = $this->actingAs($this->user)
            ->post(route('news.add-comment', ['id' => $news->id]), ['msg' => '<p>Новый комментарий</p>']);

        $comment = Comment::query()->where('relate_id', $news->id)->first();

        $this->assertNotNull($comment);
        $response->assertRedirect(route('news.view', ['id' => $news->id]) . '#comment_' . $comment->id);
        $response->assertSessionHas('success');
    }

    public function testStoreCountsTextWithoutTags(): void
    {
        $news = $this->makeNews();

        // 4 символа текста в разметке длиннее минимума
        $this->actingAs($this->user)
            ->from($news->getViewUrl())
            ->post(route('news.add-comment', ['id' => $news->id]), ['msg' => '<p><strong>Тест</strong></p>'])
            ->assertSessionHasErrors(['msg' => __('validator.length_min', ['length' => 5])]);

        $this->assertDatabaseCount('comments', 0);
    }

    public function testStoreJsonReturnsErrors(): void
    {
        $news = $this->makeNews();

        $this->actingAs($this->user)
            ->postJson(route('news.add-comment', ['id' => $news->id]), ['msg' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['msg' => __('validator.text')]);
    }

    public function testStoreJsonReturnsRedirect(): void
    {
        $news = $this->makeNews();

        $this->actingAs($this->user)
            ->postJson(route('news.add-comment', ['id' => $news->id]), ['msg' => 'Ответ через форму'])
            ->assertOk()
            ->assertJsonStructure(['redirect']);
    }

    public function testStoreToClosedNewsFails(): void
    {
        $news = $this->makeNews(['closed' => true]);

        $this->actingAs($this->user)
            ->from($news->getViewUrl())
            ->post(route('news.add-comment', ['id' => $news->id]), ['msg' => 'Комментарий к закрытой'])
            ->assertSessionHasErrors(['msg' => __('main.closed_comments')]);

        $this->assertDatabaseCount('comments', 0);
    }

    public function testStoreByGuestIsFieldError(): void
    {
        $news = $this->makeNews();

        // Ошибка формы, а не 403: форма ответа разбирает только errors
        $this->postJson(route('news.add-comment', ['id' => $news->id]), ['msg' => 'Комментарий гостя'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['msg' => __('main.not_authorized')]);

        $this->assertDatabaseCount('comments', 0);
    }

    public function testUpdateComment(): void
    {
        $comment = $this->makeComment($this->makeNews());

        $this->actingAs($this->user)
            ->patchJson('/comments/' . $comment->id, ['msg' => '<p>Исправленный текст</p>'])
            ->assertJson(['success' => true]);

        $this->assertStringContainsString('Исправленный текст', (string) $comment->fresh()->text);
    }

    public function testUpdateWithEmptyMarkupFails(): void
    {
        $comment = $this->makeComment($this->makeNews());

        $this->actingAs($this->user)
            ->patchJson('/comments/' . $comment->id, ['msg' => '<p></p>'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['msg' => __('validator.length_min', ['length' => 5])]);

        $this->assertSame('Исходный комментарий', (string) $comment->fresh()->text);
    }

    public function testUpdateInClosedNewsFails(): void
    {
        $comment = $this->makeComment($this->makeNews(['closed' => true]));

        $this->actingAs($this->user)
            ->patchJson('/comments/' . $comment->id, ['msg' => 'Исправленный текст'])
            ->assertJson([
                'success' => false,
                'message' => __('main.closed_comments'),
            ]);
    }
}
