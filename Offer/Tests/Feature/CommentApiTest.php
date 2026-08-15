<?php

namespace Modules\Offer\Tests\Feature;

use App\Models\Comment;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Offer\Models\Offer;
use Tests\ModuleTestCase;

class CommentApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Offer';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Offer::$morphName => Offer::class]);

        $this->overrideSetting('comment_text_min', 5);
        $this->overrideSetting('comment_text_max', 1000);
        $this->overrideSetting('comment_depth', 3);
        $this->overrideSetting('comment_point', 1);
        $this->overrideSetting('comment_money', 1);
        // Антифлуд для API отключаем, иначе второй комментарий в тесте не пройдет
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
    }

    public function testStoreRequiresToken(): void
    {
        $offer = $this->createOffer();

        $this->postJson('/api/comments', [
            'type' => Offer::$morphName,
            'id'   => $offer->id,
            'text' => 'Комментарий из API',
        ])->assertStatus(400);
    }

    public function testStoreComment(): void
    {
        $offer = $this->createOffer();

        $response = $this->postJson('/api/comments', [
            'type' => Offer::$morphName,
            'id'   => $offer->id,
            'text' => 'Комментарий из API',
        ], $this->headers());

        $response->assertStatus(201)
            ->assertJsonPath('comment.text', 'Комментарий из API')
            ->assertJsonPath('comment.parent_id', null)
            ->assertJsonPath('comment.depth', 0)
            ->assertJsonPath('comment.user.login', $this->user->login);

        $this->assertDatabaseHas('comments', [
            'relate_type' => Offer::$morphName,
            'relate_id'   => $offer->id,
            'user_id'     => $this->user->id,
        ]);

        // Счетчик комментариев записи растет, как и на сайте
        $this->assertSame(1, $offer->fresh()->count_comments);
    }

    public function testStoreReplyKeepsDepth(): void
    {
        $offer = $this->createOffer();

        $parent = $this->postJson('/api/comments', [
            'type' => Offer::$morphName,
            'id'   => $offer->id,
            'text' => 'Родительский комментарий',
        ], $this->headers())->json('comment.id');

        $this->postJson('/api/comments', [
            'type'      => Offer::$morphName,
            'id'        => $offer->id,
            'text'      => 'Ответ на комментарий',
            'parent_id' => $parent,
        ], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('comment.parent_id', $parent)
            ->assertJsonPath('comment.depth', 1);
    }

    public function testStoreAttachesPreloadedFiles(): void
    {
        $offer = $this->createOffer();

        // Файл загружен через /api/files до отправки комментария
        $file = File::query()->create([
            'relate_id'   => 0,
            'relate_type' => Comment::$morphName,
            'path'        => '/uploads/comments/screen.jpg',
            'name'        => 'screen.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);

        $commentId = $this->postJson('/api/comments', [
            'type' => Offer::$morphName,
            'id'   => $offer->id,
            'text' => 'Комментарий со скриншотом',
        ], $this->headers())->json('comment.id');

        $this->assertSame($commentId, $file->fresh()->relate_id);
    }

    public function testStoreValidatesTypeAndText(): void
    {
        $offer = $this->createOffer();

        $this->postJson('/api/comments', [
            'type' => 'unknown',
            'id'   => $offer->id,
            'text' => 'Комментарий из API',
        ], $this->headers())->assertStatus(422)->assertJsonValidationErrors('type');

        $this->postJson('/api/comments', [
            'type' => Offer::$morphName,
            'id'   => $offer->id,
            'text' => 'мало',
        ], $this->headers())->assertStatus(422)->assertJsonValidationErrors('text');
    }

    public function testStoreOnMissingRecord(): void
    {
        $this->postJson('/api/comments', [
            'type' => Offer::$morphName,
            'id'   => 999999,
            'text' => 'Комментарий из API',
        ], $this->headers())->assertStatus(404);
    }

    public function testShowIsAvailableForGuests(): void
    {
        $comment = $this->addComment($this->createOffer(), 'Публичный комментарий');

        $this->getJson('/api/comments/' . $comment->id)
            ->assertOk()
            ->assertJsonPath('data.id', $comment->id)
            ->assertJsonPath('data.text', 'Публичный комментарий');
    }

    public function testUpdateOwnComment(): void
    {
        $comment = $this->addComment($this->createOffer(), 'Исходный текст', $this->user->id);

        $this->patchJson('/api/comments/' . $comment->id, [
            'text' => 'Исправленный текст',
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('comment.text', 'Исправленный текст');
    }

    public function testUpdateForeignCommentIsRejected(): void
    {
        $comment = $this->addComment($this->createOffer(), 'Чужой комментарий');

        $this->patchJson('/api/comments/' . $comment->id, [
            'text' => 'Исправленный текст',
        ], $this->headers())->assertStatus(404);
    }

    public function testUpdateAfterTimeoutIsRejected(): void
    {
        $comment = $this->addComment($this->createOffer(), 'Старый комментарий', $this->user->id);
        $comment->update(['created_at' => now()->subHour()]);

        $this->patchJson('/api/comments/' . $comment->id, [
            'text' => 'Исправленный текст',
        ], $this->headers())->assertStatus(422);
    }

    public function testDestroyRequiresAdmin(): void
    {
        $offer = $this->createOffer();
        $comment = $this->addComment($offer, 'Комментарий на удаление', $this->user->id);

        $this->deleteJson('/api/comments/' . $comment->id, [], $this->headers())
            ->assertStatus(403);

        $this->user->update(['level' => User::ADMIN]);

        $this->deleteJson('/api/comments/' . $comment->id, [], $this->headers())
            ->assertOk()
            ->assertJsonPath('soft_deleted', false);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }

    private function createOffer(): Offer
    {
        return Offer::query()->create([
            'title'      => 'Test issue',
            'text'       => 'Test issue text',
            'type'       => Offer::ISSUE,
            'status'     => Offer::WAIT,
            'user_id'    => User::factory()->create()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addComment(Offer $offer, string $text, ?int $userId = null): Comment
    {
        return Comment::query()->create([
            'relate_type' => Offer::$morphName,
            'relate_id'   => $offer->id,
            'text'        => $text,
            'user_id'     => $userId ?? User::factory()->create()->id,
            'created_at'  => now(),
        ]);
    }
}
