<?php

namespace Modules\Wall\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Wall\Models\Wall;
use Tests\ModuleTestCase;

class WallApiTest extends ModuleTestCase
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

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
        $this->owner = User::factory()->create();
    }

    public function testWallIsOpenForGuests(): void
    {
        $this->createPost();

        $this->getJson('/api/walls/' . $this->owner->login)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('user.login', $this->owner->login)
            ->assertJsonStructure(['data' => [['id', 'text', 'user', 'author', 'created_at']], 'links', 'meta']);
    }

    public function testUnknownUserIsNotFound(): void
    {
        $this->getJson('/api/walls/nobody')->assertStatus(404);
    }

    public function testStoreRequiresToken(): void
    {
        $this->postJson('/api/walls/' . $this->owner->login, ['text' => 'Привет на стене'])
            ->assertStatus(400);
    }

    public function testStoreCreatesPost(): void
    {
        $this->postJson('/api/walls/' . $this->owner->login, [
            'text' => 'Привет на стене',
        ], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('post.text', 'Привет на стене')
            ->assertJsonPath('post.author.login', $this->user->login)
            ->assertJsonPath('post.user.login', $this->owner->login);

        $this->assertDatabaseHas('walls', [
            'user_id'   => $this->owner->id,
            'author_id' => $this->user->id,
        ]);
    }

    public function testShortTextIsRejected(): void
    {
        $this->postJson('/api/walls/' . $this->owner->login, ['text' => 'Ок'], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('text');
    }

    public function testOwnerClearsOwnWall(): void
    {
        $post = $this->createPost();

        $owner = $this->owner;
        $owner->update(['apikey' => Str::random(32)]);

        $this->deleteJson(
            '/api/walls/' . $owner->login . '/' . $post->id,
            [],
            ['Authorization' => 'Bearer ' . $owner->apikey],
        )->assertOk();

        $this->assertDatabaseMissing('walls', ['id' => $post->id]);
    }

    public function testForeignWallIsProtected(): void
    {
        $post = $this->createPost();

        // Чужую стену чистит только администрация
        $this->deleteJson('/api/walls/' . $this->owner->login . '/' . $post->id, [], $this->headers())
            ->assertStatus(403);

        $this->assertDatabaseHas('walls', ['id' => $post->id]);
    }

    public function testAdminDeletesAnyPost(): void
    {
        $post = $this->createPost();
        $admin = User::factory()->create(['level' => User::ADMIN, 'apikey' => Str::random(32)]);

        $this->deleteJson(
            '/api/walls/' . $this->owner->login . '/' . $post->id,
            [],
            ['Authorization' => 'Bearer ' . $admin->apikey],
        )->assertOk();

        $this->assertDatabaseMissing('walls', ['id' => $post->id]);
    }

    public function testModulePublishesCounter(): void
    {
        $this->createPost();

        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('sections.' . Wall::$morphName . '.total', 1)
            ->assertJsonPath('sections.' . Wall::$morphName . '.today', 1);
    }

    private function createPost(): Wall
    {
        return Wall::query()->create([
            'user_id'    => $this->owner->id,
            'author_id'  => $this->user->id,
            'text'       => 'Запись на стене',
            'created_at' => now(),
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }
}
