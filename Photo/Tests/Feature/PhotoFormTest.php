<?php

namespace Modules\Photo\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Photo\Models\Photo;
use Tests\ModuleTestCase;

class PhotoFormTest extends ModuleTestCase
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
        $this->overrideSetting('photo_text_min', 5);
        $this->overrideSetting('photo_text_max', 1000);
        $this->overrideSetting('floodstime', 0);
        $this->overrideSetting('fotolist', 10);

        $this->user = User::factory()->create();
    }

    public function testPhotoNeedsUploadedImage(): void
    {
        $this->actingAs($this->user)
            ->post('/photos/create', ['title' => 'Закат на море'])
            ->assertSessionHasErrors('files');

        $this->assertDatabaseCount('photos', 0);
    }

    public function testPhotoIsCreatedWithPendingImage(): void
    {
        $file = $this->pendingFile();

        // Описание необязательно, даже если в настройках задан минимум
        $this->actingAs($this->user)
            ->post('/photos/create', ['title' => 'Закат на море', 'text' => '', 'closed' => 1])
            ->assertSessionHas('success');

        $photo = Photo::query()->firstOrFail();

        $this->assertSame('Закат на море', $photo->title);
        $this->assertTrue((bool) $photo->closed);
        $this->assertSame($photo->id, $file->fresh()->relate_id);
    }

    public function testShortDescriptionIsRejected(): void
    {
        $this->pendingFile();

        $this->actingAs($this->user)
            ->post('/photos/create', ['title' => 'Закат на море', 'text' => '<p><b>Ок</b></p>'])
            ->assertSessionHasErrors('text');

        $this->assertDatabaseCount('photos', 0);
    }

    public function testFloodIsChecked(): void
    {
        $this->overrideSetting('floodstime', 60);

        $this->pendingFile();
        $this->actingAs($this->user)->post('/photos/create', ['title' => 'Закат на море'])->assertSessionHasNoErrors();

        $this->pendingFile();
        $this->actingAs($this->user)
            ->post('/photos/create', ['title' => 'Рассвет в горах'])
            ->assertSessionHasErrors(['title' => __('validator.flood', ['sec' => app(\App\Models\Flood::class)->getPeriod()])]);

        $this->assertDatabaseCount('photos', 1);
    }

    public function testGuestCannotUpload(): void
    {
        $this->get('/photos/create')->assertForbidden();
        $this->post('/photos/create', ['title' => 'Закат на море'])->assertForbidden();
    }

    public function testOwnPhotoIsEdited(): void
    {
        $photo = $this->createPhoto();

        $this->actingAs($this->user)
            ->get('/photos/' . $photo->id . '/edit')
            ->assertOk();

        $this->actingAs($this->user)
            ->post('/photos/' . $photo->id . '/edit?page=2', ['title' => 'Новое название', 'text' => 'Новое описание'])
            ->assertRedirect(route('photos.user-albums', ['user' => $this->user->login, 'page' => 2]));

        $this->assertSame('Новое название', $photo->fresh()->title);
    }

    public function testForeignPhotoIsNotEdited(): void
    {
        $photo = $this->createPhoto(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user)
            ->post('/photos/' . $photo->id . '/edit', ['title' => 'Новое название'])
            ->assertForbidden();

        $this->assertNotSame('Новое название', $photo->fresh()->title);
    }

    public function testDiscussedPhotoIsNotDeleted(): void
    {
        $photo = $this->createPhoto(['count_comments' => 1]);

        $this->actingAs($this->user)
            ->delete('/photos/' . $photo->id . '/delete')
            ->assertSessionHasErrors();

        $this->assertNotNull($photo->fresh());
    }

    public function testAdminEditsPhotoAndOnlyBossRecounts(): void
    {
        $photo = $this->createPhoto();
        $admin = User::factory()->create(['level' => User::ADMIN]);

        $this->actingAs($admin)
            ->post('/admin/photos/' . $photo->id . '/edit?page=3', ['title' => 'Правка админа'])
            ->assertRedirect(route('admin.photos.index', ['page' => 3]));

        $this->assertSame('Правка админа', $photo->fresh()->title);

        $this->actingAs($admin)->post('/admin/photos/restatement')->assertForbidden();

        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->post('/admin/photos/restatement')
            ->assertSessionHas('success');
    }

    public function testAdminListDeletesPhotoByForm(): void
    {
        $photo = $this->createPhoto();
        $admin = User::factory()->create(['level' => User::ADMIN]);

        // Ссылка отправляла GET, а маршрут удаления принимает только DELETE
        $this->actingAs($admin)
            ->get('/admin/photos')
            ->assertOk()
            ->assertSee('action="' . route('admin.photos.delete', ['id' => $photo->id, 'page' => 1]) . '" method="post"', false);

        $this->actingAs($admin)
            ->delete('/admin/photos/' . $photo->id . '/delete?page=2')
            ->assertRedirect(route('admin.photos.index', ['page' => 2]));

        $this->assertNull($photo->fresh());
    }

    private function pendingFile(): File
    {
        return File::factory()->create([
            'relate_id'   => 0,
            'relate_type' => Photo::$morphName,
            'path'        => '/uploads/photos/sunset.jpg',
            'name'        => 'sunset.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);
    }

    private function createPhoto(array $attributes = []): Photo
    {
        return Photo::factory()->create($attributes + [
            'user_id'    => $this->user->id,
            'title'      => 'Старое фото',
            'text'       => '',
            'closed'     => 0,
            'created_at' => now(),
        ]);
    }
}
