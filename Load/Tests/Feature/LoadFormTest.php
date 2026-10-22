<?php

namespace Modules\Load\Tests\Feature;

use App\Models\File;
use App\Models\Flood;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;
use Tests\ModuleTestCase;

class LoadFormTest extends ModuleTestCase
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
        $this->overrideSetting('down_category_min', 3);
        $this->overrideSetting('down_category_max', 50);
        $this->overrideSetting('maxfiles', 5);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
        $this->category = Load::factory()->create(['parent_id' => 0, 'name' => 'Программы', 'sort' => 1]);
    }

    public function testDownIsCreatedWithPendingFile(): void
    {
        $file = $this->file(0);

        $this->actingAs($this->user)
            ->get('/downs/create?category=' . $this->category->id)
            ->assertOk()
            ->assertSee('name="category_id"', false);

        $this->actingAs($this->user)
            ->post('/downs/create', $this->payload())
            ->assertSessionHas('success');

        $down = Down::query()->where('title', 'Архив с обоями')->firstOrFail();

        $this->assertFalse((bool) $down->active);
        $this->assertSame($down->id, $file->fresh()->relate_id);
    }

    public function testDownNeedsFileOrLink(): void
    {
        $this->actingAs($this->user)
            ->post('/downs/create', $this->payload())
            ->assertSessionHasErrors('files');

        $this->assertDatabaseCount('downs', 0);
    }

    public function testLinksAreIgnoredWhenDisabled(): void
    {
        $this->overrideSetting('down_allow_links', 0);
        $this->file(0);

        // Раньше при создании ссылки сохранялись и при выключенной настройке
        $this->actingAs($this->user)
            ->post('/downs/create', ['links' => ['https://example.com/file.zip']] + $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('downs', ['title' => 'Архив с обоями', 'links' => null]);
    }

    public function testFormErrorsAreShownUnderTheirField(): void
    {
        $this->category->update(['closed' => 1]);
        Down::factory()->create([
            'category_id' => $this->category->id,
            'title'       => 'Архив с обоями',
            'text'        => 'Описание чужого архива',
            'user_id'     => User::factory()->create()->id,
            'active'      => 1,
            'created_at'  => now(),
        ]);

        $this->actingAs($this->user)
            ->post('/downs/create', ['text' => '<p><b>Коротко</b></p>', 'links' => ['не ссылка']] + $this->payload())
            ->assertSessionHasErrors(['category_id', 'title', 'text', 'links.0']);
    }

    public function testFloodIsShownUnderTitle(): void
    {
        $this->overrideSetting('floodstime', 60);

        $this->file(0);
        $this->actingAs($this->user)->post('/downs/create', $this->payload())->assertSessionHasNoErrors();

        $this->file(0);
        $this->actingAs($this->user)
            ->post('/downs/create', ['title' => 'Другой архив'] + $this->payload())
            ->assertSessionHasErrors(['title' => __('validator.flood', ['sec' => app(Flood::class)->getPeriod()])]);
    }

    public function testClosedUploadIsRefusedBeforeFieldErrors(): void
    {
        $this->overrideSetting('downupload', 0);

        // Раньше пользователь сначала исправлял ошибки полей и только потом узнавал, что приём закрыт
        $this->actingAs($this->user)
            ->post('/downs/create', ['title' => ''] + $this->payload())
            ->assertForbidden()
            ->assertSee(__('load::loads.down_closed'));

        $this->assertDatabaseCount('downs', 0);
    }

    public function testGuestCannotUpload(): void
    {
        $this->get('/downs/create')->assertForbidden();
        $this->post('/downs/create', $this->payload())->assertForbidden();
    }

    public function testOwnDownIsEdited(): void
    {
        $down = $this->createDown();
        $this->file($down->id);

        $this->actingAs($this->user)
            ->get('/downs/' . $down->id . '/edit')
            ->assertOk();

        $this->actingAs($this->user)
            ->post('/downs/' . $down->id . '/edit', ['title' => 'Новые обои'] + $this->payload())
            ->assertRedirect(route('downs.view', ['id' => $down->id]));

        $this->assertSame('Новые обои', $down->fresh()->title);
    }

    public function testUnknownCategoryIsFormError(): void
    {
        $down = $this->createDown();
        $this->file($down->id);

        // Раньше несуществующий раздел при правке ронял страницу с 500
        $this->actingAs($this->user)
            ->post('/downs/' . $down->id . '/edit', ['category_id' => 999999] + $this->payload())
            ->assertSessionHasErrors('category_id');
    }

    public function testDownStaysInItsClosedCategory(): void
    {
        $down = $this->createDown();
        $this->file($down->id);
        $this->category->update(['closed' => 1]);

        $this->actingAs($this->user)
            ->post('/downs/' . $down->id . '/edit', ['title' => 'Новые обои'] + $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame('Новые обои', $down->fresh()->title);
    }

    public function testUnverifiedDownIsShownOnlyToAuthorAndAdmins(): void
    {
        $down = $this->createDown();
        $hidden = __('load::loads.down_not_verified');

        // Раньше гость видел непроверенную загрузку, хотя вошедший пользователь — нет
        $this->get('/downs/' . $down->id)->assertSee($hidden)->assertDontSee('Описание архива с обоями');

        $this->actingAs(User::factory()->create())
            ->get('/downs/' . $down->id)
            ->assertSee($hidden);

        $this->actingAs($this->user)
            ->get('/downs/' . $down->id)
            ->assertSee('Описание архива с обоями');

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->get('/downs/' . $down->id)
            ->assertSee('Описание архива с обоями');
    }

    public function testVerifiedDownIsNotEdited(): void
    {
        $down = $this->createDown(['active' => 1]);

        $this->actingAs($this->user)
            ->post('/downs/' . $down->id . '/edit', ['title' => 'Новые обои'] + $this->payload())
            ->assertSee(__('load::loads.down_verified'));

        $this->assertSame('Архив с обоями', $down->fresh()->title);
    }

    public function testAdminEditsDown(): void
    {
        $down = $this->createDown(['active' => 1]);
        $this->file($down->id);
        $this->category->increment('count_downs');
        $other = Load::factory()->create(['parent_id' => 0, 'name' => 'Игры', 'sort' => 2]);

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->post('/admin/downs/' . $down->id . '/edit', ['category_id' => $other->id] + $this->payload())
            ->assertRedirect(route('admin.downs.edit', ['id' => $down->id]));

        $this->assertSame($other->id, $down->fresh()->category_id);
        $this->assertSame(0, $this->category->fresh()->count_downs);
        $this->assertSame(1, $other->fresh()->count_downs);
    }

    public function testAdminMovesDownToClosedCategory(): void
    {
        $down = $this->createDown(['active' => 1]);
        $this->file($down->id);
        $archive = Load::factory()->create(['name' => 'Архив', 'closed' => 1]);

        // Пользователь в закрытый раздел не переносит, администратор — да, как и до перевода на FormRequest
        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->post('/admin/downs/' . $down->id . '/edit', ['category_id' => $archive->id] + $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame($archive->id, $down->fresh()->category_id);
    }

    public function testBossManagesCategories(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->post('/admin/loads/create', ['name' => 'Музыка'])
            ->assertForbidden();

        // Ошибка имени раньше уходила под ключ title, а поле называется name
        $this->actingAs($boss)
            ->post('/admin/loads/create', ['name' => 'Ок'])
            ->assertSessionHasErrors('name');

        $this->actingAs($boss)
            ->post('/admin/loads/' . $this->category->id . '/edit', ['name' => 'Программы', 'parent' => $this->category->id])
            ->assertSessionHasErrors('parent');

        $this->actingAs($boss)
            ->post('/admin/loads/' . $this->category->id . '/edit', ['name' => 'Софт', 'parent' => 0, 'closed' => 1])
            ->assertRedirect(route('admin.loads.index'));

        $this->assertSame('Софт', $this->category->fresh()->name);

        $this->createDown();

        $this->actingAs($boss)
            ->delete('/admin/loads/' . $this->category->id . '/delete')
            ->assertSessionHasErrors();

        $this->assertNotNull($this->category->fresh());
    }

    public function testOnlyBossDeletesDown(): void
    {
        $down = $this->createDown();

        // Раньше удалить загрузку мог любой администратор
        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->delete('/admin/downs/delete/' . $down->id)
            ->assertForbidden();

        $this->assertNotNull($down->fresh());

        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->delete('/admin/downs/delete/' . $down->id)
            ->assertRedirect();

        $this->assertNull($down->fresh());
    }

    public function testDownWithoutActiveIsNotSearchable(): void
    {
        // В миграции active по умолчанию false: такая загрузка ждёт проверки и в поиск не идёт
        $down = Down::factory()->create();

        $this->assertDatabaseMissing('search', ['relate_type' => Down::$morphName, 'relate_id' => $down->id]);
    }

    public function testSingleFileWithoutBracketsIsFormError(): void
    {
        // Раньше один файл без [] ронял запрос с 500
        $this->postJson('/api/downs', ['files' => UploadedFile::fake()->create('one.zip', 10)] + $this->payload(), ['Authorization' => 'Bearer ' . $this->user->apikey])
            ->assertStatus(422)
            ->assertJsonValidationErrors('files');
    }

    public function testApiIgnoresLinksWhenDisabled(): void
    {
        $this->overrideSetting('down_allow_links', 0);
        $this->file(0);

        $this->postJson('/api/downs', ['links' => ['https://example.com/file.zip']] + $this->payload(), ['Authorization' => 'Bearer ' . $this->user->apikey])
            ->assertStatus(201);

        $this->assertDatabaseHas('downs', ['title' => 'Архив с обоями', 'links' => null]);
    }

    private function payload(): array
    {
        return [
            'category_id' => $this->category->id,
            'title'       => 'Архив с обоями',
            'text'        => '<p>Описание архива с обоями</p>',
        ];
    }

    private function file(int $relateId): File
    {
        return File::factory()->create([
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

    private function createDown(array $attributes = []): Down
    {
        return Down::factory()->create($attributes + [
            'category_id' => $this->category->id,
            'title'       => 'Архив с обоями',
            'text'        => 'Описание архива с обоями',
            'user_id'     => $this->user->id,
            'active'      => 0,
            'created_at'  => now(),
        ]);
    }
}
