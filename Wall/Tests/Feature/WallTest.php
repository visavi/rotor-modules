<?php

namespace Modules\Wall\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Wall\Models\Wall;
use Tests\ModuleTestCase;

class WallTest extends ModuleTestCase
{
    protected string $moduleName = 'Wall';

    private User $user;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Wall::$morphName => Wall::class]);

        $this->overrideSetting('comment_text_min', 5);
        $this->overrideSetting('comment_text_max', 1000);
        $this->overrideSetting('wallpost', 10);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create();
        $this->owner = User::factory()->create();
    }

    public function testPostIsAdded(): void
    {
        $this->actingAs($this->user)
            ->post('/walls/' . $this->owner->login . '/create', ['text' => 'Привет на стене'])
            ->assertRedirect('walls/' . $this->owner->login)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('walls', [
            'user_id'   => $this->owner->id,
            'author_id' => $this->user->id,
            'text'      => 'Привет на стене',
        ]);
    }

    public function testShortTextShowsError(): void
    {
        $wall = '/walls/' . $this->owner->login;

        $this->actingAs($this->user)
            ->from($wall)
            ->post($wall . '/create', ['text' => 'Ок'])
            ->assertRedirect($wall)
            ->assertSessionHasErrors('text');

        $this->assertDatabaseCount('walls', 0);
    }

    public function testMarkupIsNotCountedInLength(): void
    {
        $this->actingAs($this->user)
            ->post('/walls/' . $this->owner->login . '/create', ['text' => '<p>' . Str::repeat('a', 1000) . '</p>'])
            ->assertSessionHasNoErrors();
    }

    public function testFloodIsChecked(): void
    {
        $this->overrideSetting('floodstime', 60);

        $this->actingAs($this->user)
            ->post('/walls/' . $this->owner->login . '/create', ['text' => 'Первая запись'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->post('/walls/' . $this->owner->login . '/create', ['text' => 'Вторая запись'])
            ->assertSessionHasErrors('text');

        $this->assertDatabaseCount('walls', 1);
    }

    public function testGuestCannotPost(): void
    {
        $this->post('/walls/' . $this->owner->login . '/create', ['text' => 'Привет от гостя'])
            ->assertForbidden();

        $this->assertDatabaseCount('walls', 0);
    }

    public function testUnknownWallIsNotFound(): void
    {
        $this->actingAs($this->user)
            ->post('/walls/nobody/create', ['text' => 'Привет на стене'])
            ->assertNotFound();
    }

    public function testOwnerDeletesPost(): void
    {
        $post = $this->wallPost();

        $this->actingAs($this->owner)
            ->postJson('/walls/' . $this->owner->login . '/delete', ['id' => $post->id], $this->ajax())
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('walls', 0);
    }

    public function testStrangerCannotDeletePost(): void
    {
        $post = $this->wallPost();

        $this->actingAs($this->user)
            ->postJson('/walls/' . $this->owner->login . '/delete', ['id' => $post->id], $this->ajax())
            ->assertForbidden()
            ->assertJsonPath('message', __('main.deleted_only_admins'));

        $this->assertDatabaseCount('walls', 1);
    }

    private function wallPost(): Wall
    {
        return Wall::factory()->create([
            'user_id'   => $this->owner->id,
            'author_id' => $this->user->id,
            'text'      => 'Запись на стене',
        ]);
    }

    /**
     * Удаление идёт только запросом из браузера
     */
    private function ajax(): array
    {
        return ['X-Requested-With' => 'XMLHttpRequest'];
    }
}
