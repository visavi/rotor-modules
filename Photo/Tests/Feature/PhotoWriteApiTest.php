<?php

namespace Modules\Photo\Tests\Feature;

use App\Models\Comment;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Photo\Models\Photo;
use Tests\ModuleTestCase;

class PhotoWriteApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Photo';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Photo::$morphName => Photo::class]);

        $this->overrideSetting('photos_create', 1);
        $this->overrideSetting('photo_title_min', 5);
        $this->overrideSetting('photo_title_max', 50);
        $this->overrideSetting('photo_text_min', 0);
        $this->overrideSetting('photo_text_max', 1000);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
    }

    public function testStoreRequiresToken(): void
    {
        $this->postJson('/api/photos', ['title' => 'Прогулка по лесу'])->assertStatus(400);
    }

    public function testStoreRequiresUploadedPhoto(): void
    {
        // Снимков в очереди нет — запись создавать не из чего
        $this->postJson('/api/photos', [
            'title' => 'Прогулка по лесу',
            'text'  => 'Описание прогулки',
        ], $this->headers())->assertStatus(422);
    }

    public function testStoreCreatesPhoto(): void
    {
        $file = $this->pendingFile();

        $response = $this->postJson('/api/photos', [
            'title' => 'Прогулка по лесу',
            'text'  => 'Описание прогулки',
        ], $this->headers());

        $response->assertStatus(201)
            ->assertJsonPath('photo.title', 'Прогулка по лесу')
            ->assertJsonPath('photo.user.login', $this->user->login);

        $this->assertSame($response->json('photo.id'), $file->fresh()->relate_id);
    }

    public function testStoreAcceptsPhotoInRequest(): void
    {
        // Снимок в теле запроса заменяет предварительную загрузку
        $this->post('/api/photos', [
            'title' => 'Прогулка по лесу',
            'files' => [UploadedFile::fake()->image('forest.jpg', 300, 300)],
        ], $this->headers())
            ->assertStatus(201)
            ->assertJsonCount(1, 'photo.media');
    }

    public function testStoreIsBlockedWhenSectionClosed(): void
    {
        $this->overrideSetting('photos_create', 0);
        $this->pendingFile();

        $this->postJson('/api/photos', ['title' => 'Прогулка по лесу'], $this->headers())
            ->assertStatus(422);
    }

    public function testUpdateOwnPhoto(): void
    {
        $photo = $this->createPhoto($this->user->id);

        $this->patchJson('/api/photos/' . $photo->id, [
            'title'  => 'Изменённый заголовок',
            'text'   => 'Изменённое описание',
            'closed' => true,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('photo.title', 'Изменённый заголовок')
            ->assertJsonPath('photo.closed', true);
    }

    public function testForeignPhotoIsProtected(): void
    {
        $photo = $this->createPhoto(User::factory()->create()->id);

        $this->patchJson('/api/photos/' . $photo->id, ['title' => 'Изменённый заголовок'], $this->headers())
            ->assertStatus(404);
        $this->deleteJson('/api/photos/' . $photo->id, [], $this->headers())->assertStatus(404);
    }

    public function testDestroyRemovesPhoto(): void
    {
        $photo = $this->createPhoto($this->user->id);

        $this->deleteJson('/api/photos/' . $photo->id, [], $this->headers())->assertOk();

        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    public function testDiscussedPhotoIsNotDeleted(): void
    {
        $photo = $this->createPhoto($this->user->id);

        Comment::query()->create([
            'relate_type' => Photo::$morphName,
            'relate_id'   => $photo->id,
            'text'        => 'Красивое фото',
            'user_id'     => $this->user->id,
            'created_at'  => now(),
        ]);
        $photo->update(['count_comments' => 1]);

        $this->deleteJson('/api/photos/' . $photo->id, [], $this->headers())->assertStatus(422);

        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
    }

    private function pendingFile(): File
    {
        return File::query()->create([
            'relate_id'   => 0,
            'relate_type' => Photo::$morphName,
            'path'        => '/uploads/photos/forest.jpg',
            'name'        => 'forest.jpg',
            'size'        => 2048,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }

    private function createPhoto(int $userId): Photo
    {
        return Photo::query()->create([
            'user_id'    => $userId,
            'title'      => 'Прогулка по лесу',
            'text'       => 'Описание прогулки',
            'created_at' => now(),
        ]);
    }
}
