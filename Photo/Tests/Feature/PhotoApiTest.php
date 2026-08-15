<?php

namespace Modules\Photo\Tests\Feature;

use App\Classes\Registry;
use App\Models\Comment;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Photo\Models\Photo;
use Tests\ModuleTestCase;

class PhotoApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Photo';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Photo::$morphName => Photo::class]);

        // Типы регистрирует ModuleServiceProvider, в тестах модуль поднимается вручную
        Registry::ratingType(Photo::$morphName);

        $this->user = User::factory()->create();
    }

    public function testViewIsAvailableForGuests(): void
    {
        $photo = $this->createPhoto();

        $response = $this->getJson('/api/photos/' . $photo->id);

        $response->assertOk();
        $response->assertJsonPath('photo.id', $photo->id);
        $response->assertJsonPath('photo.title', 'Test photo');
        $response->assertJsonPath('photo.url', $photo->getViewUrl());
        $response->assertJsonPath('photo.user.login', $this->user->login);
        $response->assertJsonPath('photo.breadcrumbs.0.title', __('photo::photos.photos'));
        $response->assertJsonPath('photo.vote.value', null);
        $response->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function testGalleryKeepsMediaInsertedIntoText(): void
    {
        $photo = $this->createPhoto('<p>Снимок: <img src="/uploads/photos/inline.jpg"></p>');

        $this->attachFile($photo, 'inline.jpg', 'jpg', 'image/jpeg');
        $this->attachFile($photo, 'second.jpg', 'jpg', 'image/jpeg');

        $response = $this->getJson('/api/photos/' . $photo->id);

        $response->assertOk();
        // Снимки — само содержимое записи, поэтому в галерее оба
        $response->assertJsonCount(2, 'photo.media');
    }

    public function testViewReturnsCommentsPaginated(): void
    {
        $photo = $this->createPhoto();

        $first = $this->addComment($photo, 'First comment');
        $this->addComment($photo, 'Second comment', $first->id);

        $response = $this->getJson('/api/photos/' . $photo->id . '?per_page=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 2);

        $this->getJson('/api/photos/' . $photo->id . '?per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.parent_id', $first->id);
    }

    public function testViewReturnsVoteOfCurrentUser(): void
    {
        $photo = $this->createPhoto();
        $voter = User::factory()->create(['apikey' => Str::random(32)]);

        $this->postJson('/api/rating', [
            'type' => Photo::$morphName,
            'id'   => $photo->id,
            'vote' => '+',
        ], ['Authorization' => 'Bearer ' . $voter->apikey])->assertOk();

        $this->getJson('/api/photos/' . $photo->id, ['Authorization' => 'Bearer ' . $voter->apikey])
            ->assertOk()
            ->assertJsonPath('photo.vote.value', '+')
            ->assertJsonPath('photo.vote.own', false);
    }

    public function testIndexReturnsList(): void
    {
        $this->createPhoto();
        $this->createPhoto();

        $response = $this->getJson('/api/photos?per_page=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 2);
    }

    public function testViewNotFound(): void
    {
        $this->getJson('/api/photos/100')->assertStatus(404);
    }

    private function createPhoto(?string $text = 'Test photo text'): Photo
    {
        return Photo::query()->create([
            'title'      => 'Test photo',
            'text'       => $text,
            'user_id'    => $this->user->id,
            'created_at' => now(),
        ]);
    }

    private function attachFile(Photo $photo, string $name, string $extension, string $mimeType): void
    {
        File::query()->create([
            'relate_id'   => $photo->id,
            'relate_type' => Photo::$morphName,
            'path'        => '/uploads/photos/' . $name,
            'name'        => $name,
            'size'        => 1024,
            'extension'   => $extension,
            'mime_type'   => $mimeType,
            'user_id'     => $this->user->id,
        ]);
    }

    private function addComment(Photo $photo, string $text, ?int $parentId = null): Comment
    {
        return Comment::query()->create([
            'relate_type' => Photo::$morphName,
            'relate_id'   => $photo->id,
            'parent_id'   => $parentId,
            'text'        => $text,
            'user_id'     => $this->user->id,
            'created_at'  => now(),
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
        ]);
    }
}
