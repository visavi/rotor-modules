<?php

namespace Modules\Load\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;
use Tests\ModuleTestCase;

class DownWriteApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Load';

    private User $user;

    private Load $category;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Down::$morphName => Down::class]);

        $this->overrideSetting('downupload', 1);
        $this->overrideSetting('down_title_min', 5);
        $this->overrideSetting('down_title_max', 50);
        $this->overrideSetting('down_text_min', 10);
        $this->overrideSetting('down_text_max', 1000);
        $this->overrideSetting('down_link_min', 5);
        $this->overrideSetting('down_link_max', 100);
        $this->overrideSetting('down_allow_links', 1);
        $this->overrideSetting('maxfiles', 5);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
        $this->category = Load::query()->create(['parent_id' => 0, 'name' => 'Программы', 'sort' => 1]);
    }

    public function testStoreRequiresToken(): void
    {
        $this->postJson('/api/downs', $this->payload())->assertStatus(400);
    }

    public function testStoreRequiresFileOrLink(): void
    {
        // Ни дистрибутива, ни ссылки — загружать нечего
        $this->postJson('/api/downs', $this->payload(), $this->headers())->assertStatus(422);
    }

    public function testStoreCreatesUnverifiedDown(): void
    {
        $file = $this->pendingFile();

        $response = $this->postJson('/api/downs', $this->payload(), $this->headers());

        $response->assertStatus(201)
            ->assertJsonPath('down.title', 'Архив с обоями')
            ->assertJsonPath('down.category.id', $this->category->id)
            // Загрузка ждёт проверки модератором
            ->assertJsonPath('down.active', false);

        $this->assertSame($response->json('down.id'), $file->fresh()->relate_id);
        // Счётчик категории растёт только после проверки
        $this->assertSame(0, $this->category->fresh()->count_downs);
    }

    public function testStoreAcceptsLinkInsteadOfFile(): void
    {
        $this->postJson('/api/downs', $this->payload() + ['links' => ['https://example.com/file.zip']], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('down.links.0.name', 'file.zip');
    }

    public function testDuplicateTitleIsRejected(): void
    {
        $this->pendingFile();
        $this->createDown($this->user->id);

        $this->postJson('/api/downs', $this->payload(), $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function testStoreIsBlockedWhenUploadClosed(): void
    {
        $this->overrideSetting('downupload', 0);
        $this->pendingFile();

        $this->postJson('/api/downs', $this->payload(), $this->headers())->assertStatus(422);
    }

    public function testClosedCategoryIsRejected(): void
    {
        $this->category->update(['closed' => 1]);
        $this->pendingFile();

        $this->postJson('/api/downs', $this->payload(), $this->headers())->assertStatus(422);
    }

    public function testUpdateOwnDown(): void
    {
        $down = $this->createDown($this->user->id);
        $this->attachedFile($down);

        $this->patchJson('/api/downs/' . $down->id, [
            'category_id' => $this->category->id,
            'title'       => 'Новый архив с обоями',
            'text'        => 'Изменённое описание загрузки',
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('down.title', 'Новый архив с обоями');
    }

    public function testVerifiedDownIsNotEditable(): void
    {
        $down = $this->createDown($this->user->id);
        $this->attachedFile($down);
        $down->update(['active' => 1]);

        $this->patchJson('/api/downs/' . $down->id, [
            'category_id' => $this->category->id,
            'title'       => 'Новый архив с обоями',
            'text'        => 'Изменённое описание загрузки',
        ], $this->headers())->assertStatus(422);
    }

    public function testForeignDownIsProtected(): void
    {
        $down = $this->createDown(User::factory()->create()->id);

        $this->patchJson('/api/downs/' . $down->id, [
            'category_id' => $this->category->id,
            'title'       => 'Новый архив с обоями',
            'text'        => 'Изменённое описание загрузки',
        ], $this->headers())->assertStatus(404);
    }

    private function payload(): array
    {
        return [
            'category_id' => $this->category->id,
            'title'       => 'Архив с обоями',
            'text'        => 'Описание архива с обоями',
        ];
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }

    private function pendingFile(): File
    {
        return $this->file(0);
    }

    private function attachedFile(Down $down): File
    {
        return $this->file($down->id);
    }

    private function file(int $relateId): File
    {
        return File::query()->create([
            'relate_id'   => $relateId,
            'relate_type' => Down::$morphName,
            'path'        => '/uploads/files/wallpapers.zip',
            'name'        => 'wallpapers.zip',
            'size'        => 4096,
            'extension'   => 'zip',
            'mime_type'   => 'application/zip',
            'user_id'     => $this->user->id,
        ]);
    }

    private function createDown(int $userId): Down
    {
        return Down::query()->create([
            'category_id' => $this->category->id,
            'title'       => 'Архив с обоями',
            'text'        => 'Описание архива с обоями',
            'user_id'     => $userId,
            'active'      => 0,
            'created_at'  => now(),
        ]);
    }
}
