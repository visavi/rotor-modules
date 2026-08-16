<?php

namespace Modules\Load\Tests\Feature;

use App\Models\Comment;
use App\Models\File;
use App\Models\User;
use App\Support\Registry;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;
use Tests\ModuleTestCase;

class DownApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Load';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Down::$morphName => Down::class]);

        // Типы регистрирует ModuleServiceProvider, в тестах модуль поднимается вручную
        Registry::ratingType(Down::$morphName);

        $this->overrideSetting('down_guest_download', 1);

        $this->user = User::factory()->create();
    }

    public function testViewIsAvailableForGuests(): void
    {
        $down = $this->createDown();

        $response = $this->getJson('/api/downs/' . $down->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $down->id);
        $response->assertJsonPath('data.title', 'Test down');
        $response->assertJsonPath('data.url', $down->getViewUrl());
        $response->assertJsonPath('data.user.login', $this->user->login);
        $response->assertJsonPath('data.breadcrumbs.0.title', __('load::loads.loads'));
        $response->assertJsonPath('data.breadcrumbs.1.title', 'Test category');
        // Категория записи приходит объектом, как раздел у темы форума
        $response->assertJsonPath('data.category_id', $down->category_id);
        $response->assertJsonPath('data.category.name', 'Test category');
        $response->assertJsonPath('data.category.parent', null);
        $response->assertJsonStructure(['data', 'comments' => ['data', 'links', 'meta']]);
    }

    public function testFilesAreServedThroughDownloadRoute(): void
    {
        $down = $this->createDown();

        $archive = $this->attachFile($down, 'game.zip', 'zip', 'application/zip');
        $this->attachFile($down, 'screen.jpg', 'jpg', 'image/jpeg');

        $response = $this->getJson('/api/downs/' . $down->id);

        $response->assertOk();
        // Картинка идёт в галерею, дистрибутив — в files со ссылкой на роут скачивания
        $response->assertJsonCount(1, 'data.media');
        $response->assertJsonPath('data.media.0.name', 'screen.jpg');
        $response->assertJsonCount(1, 'data.files');
        $response->assertJsonPath('data.files.0.name', 'game.zip');
        $response->assertJsonPath('data.files.0.download_url', route('downs.download', ['id' => $down->id, 'fid' => $archive->id]));
        $response->assertJsonPath('data.files.0.archive_url', route('downs.zip', ['id' => $down->id, 'fid' => $archive->id]));
        // Прямой путь к файлу дистрибутива в ответ не попадает
        $response->assertJsonMissingPath('data.files.0.path');
    }

    public function testGuestDownloadCanBeDisabled(): void
    {
        $this->overrideSetting('down_guest_download', 0);

        $down = $this->createDown();
        $this->attachFile($down, 'game.zip', 'zip', 'application/zip');

        $response = $this->getJson('/api/downs/' . $down->id);

        $response->assertOk();
        $response->assertJsonPath('data.can_download', false);
        $response->assertJsonPath('data.files.0.download_url', null);
    }

    public function testInactiveDownIsHiddenFromStrangers(): void
    {
        $down = $this->createDown(false);

        $this->getJson('/api/downs/' . $down->id)->assertStatus(403);

        // Автор свою непроверенную загрузку видит
        $author = User::query()->find($this->user->id);
        $author->update(['apikey' => Str::random(32)]);

        $this->getJson('/api/downs/' . $down->id, ['Authorization' => 'Bearer ' . $author->apikey])
            ->assertOk()
            ->assertJsonPath('data.active', false);
    }

    public function testViewReturnsCommentsPaginated(): void
    {
        $down = $this->createDown();

        $first = $this->addComment($down, 'First comment');
        $this->addComment($down, 'Second comment', $first->id);

        $response = $this->getJson('/api/downs/' . $down->id . '?per_page=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'comments.data');
        $response->assertJsonPath('comments.meta.total', 2);

        $this->getJson('/api/downs/' . $down->id . '?per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('comments.data.0.parent_id', $first->id);
    }

    public function testViewReturnsVoteOfCurrentUser(): void
    {
        $down = $this->createDown();
        $voter = User::factory()->create(['apikey' => Str::random(32)]);

        $this->postJson('/api/rating', [
            'type' => Down::$morphName,
            'id'   => $down->id,
            'vote' => '+',
        ], ['Authorization' => 'Bearer ' . $voter->apikey])->assertOk();

        $this->getJson('/api/downs/' . $down->id, ['Authorization' => 'Bearer ' . $voter->apikey])
            ->assertOk()
            ->assertJsonPath('data.vote.value', '+')
            ->assertJsonPath('data.vote.own', false);
    }

    public function testIndexFiltersByCategory(): void
    {
        $down = $this->createDown();

        $other = Load::query()->create(['name' => 'Other category']);
        Down::query()->create([
            'category_id' => $other->id,
            'title'       => 'Other down',
            'text'        => 'Other down text',
            'user_id'     => $this->user->id,
            'active'      => true,
            'created_at'  => now(),
        ]);

        $this->getJson('/api/downs')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/downs?category_id=' . $down->category_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Test down');
    }

    public function testIndexHidesUnverifiedDowns(): void
    {
        $this->createDown();
        $this->createDown(false);

        $this->getJson('/api/downs')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function testCategoriesReturnTree(): void
    {
        $parent = Load::query()->create(['name' => 'Parent category']);
        Load::query()->create(['name' => 'Child category', 'parent_id' => $parent->id]);

        $this->getJson('/api/loads')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Parent category')
            ->assertJsonPath('data.0.children.0.name', 'Child category');
    }

    public function testViewNotFound(): void
    {
        $this->getJson('/api/downs/100')->assertStatus(404);
    }

    private function createDown(bool $active = true): Down
    {
        $load = Load::query()->create(['name' => 'Test category']);

        return Down::query()->create([
            'category_id' => $load->id,
            'title'       => 'Test down',
            'text'        => 'Test down text',
            'user_id'     => $this->user->id,
            'active'      => $active,
            'created_at'  => now(),
        ]);
    }

    private function attachFile(Down $down, string $name, string $extension, string $mimeType): File
    {
        return File::query()->create([
            'relate_id'   => $down->id,
            'relate_type' => Down::$morphName,
            'path'        => '/uploads/files/' . $name,
            'name'        => $name,
            'size'        => 2048,
            'extension'   => $extension,
            'mime_type'   => $mimeType,
            'user_id'     => $this->user->id,
        ]);
    }

    private function addComment(Down $down, string $text, ?int $parentId = null): Comment
    {
        return Comment::query()->create([
            'relate_type' => Down::$morphName,
            'relate_id'   => $down->id,
            'parent_id'   => $parentId,
            'text'        => $text,
            'user_id'     => $this->user->id,
            'created_at'  => now(),
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
        ]);
    }
}
