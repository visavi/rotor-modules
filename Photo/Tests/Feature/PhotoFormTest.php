<?php

namespace Modules\Photo\Tests\Feature;

use App\Models\File;
use App\Models\Setting;
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

    public function testUploadAwardsPointsAndDeleteTakesThemBack(): void
    {
        $this->overrideSetting('photo_point', 2);
        $this->overrideSetting('photo_money', 100);
        $this->user->update(['point' => 10, 'money' => 1000]);

        $this->pendingFile();
        $this->actingAs($this->user)
            ->post('/photos/create', ['title' => 'Закат на море'])
            ->assertSessionHas('success');

        $this->user->refresh();
        $this->assertSame(12, $this->user->point);
        $this->assertSame(1100, $this->user->money);

        // Иначе загрузка и удаление по кругу копили бы баллы
        $this->actingAs($this->user)
            ->delete('/photos/' . Photo::query()->firstOrFail()->id . '/delete')
            ->assertSessionHas('success');

        $this->user->refresh();
        $this->assertSame(10, $this->user->point);
        $this->assertSame(1000, $this->user->money);
    }

    public function testDeleteDoesNotTakeMorePointsThanUserHas(): void
    {
        $this->overrideSetting('photo_point', 0);
        $this->overrideSetting('photo_money', 0);
        $photo = $this->createPhoto();

        $this->overrideSetting('photo_point', 5);
        $this->overrideSetting('photo_money', 500);
        $this->user->update(['point' => 3, 'money' => 200]);

        $this->actingAs($this->user)->delete('/photos/' . $photo->id . '/delete');

        $this->user->refresh();
        $this->assertSame(0, $this->user->point);
        $this->assertSame(0, $this->user->money);
    }

    public function testPhotoOfDeletedAuthorDoesNotRewardOthers(): void
    {
        $this->overrideSetting('photo_point', 2);
        $this->overrideSetting('photo_money', 100);
        $this->user->update(['point' => 10, 'money' => 1000]);

        // withDefault отдаёт пустую модель — increment на ней не должен задеть всю таблицу
        $photo = $this->createPhoto(['user_id' => 0]);
        $photo->delete();

        $this->user->refresh();
        $this->assertSame(10, $this->user->point);
        $this->assertSame(1000, $this->user->money);
    }

    public function testFaqListsPhotoReward(): void
    {
        $this->overrideSetting('photo_point', 2);
        $this->overrideSetting('photo_money', 100);

        $this->get('/faq')
            ->assertOk()
            ->assertSee(__('photo::photos.faq_reward'));
    }

    public function testAdminSettingsSavePoints(): void
    {
        // Миграция настроек на установленном сайте уже прошла — баллы добавляет отдельная
        foreach (glob(__DIR__ . '/../../database/migrations/*_insert_photo_*settings.php') ?: [] as $migration) {
            (require $migration)->up();
        }
        Setting::flush();

        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($boss)
            ->get('/admin/photo-settings')
            ->assertOk()
            ->assertSee('name="sets[photo_point]"', false)
            ->assertSee('name="sets[photo_money]"', false);

        $this->actingAs($boss)
            ->post('/admin/photo-settings', ['sets' => ['photo_point' => 3, 'photo_money' => 150]])
            ->assertSessionHas('success');

        $this->assertSame(3, setting('photo_point'));
        $this->assertSame(150, setting('photo_money'));
    }

    public function testAdminListLinksToGalleryWithEye(): void
    {
        $this->createPhoto();

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->get('/admin/photos')
            ->assertOk()
            ->assertSee('href="' . route('photos.index', ['page' => 1]) . '"><i class="fas fa-eye"></i>', false);
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
