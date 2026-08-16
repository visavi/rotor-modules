<?php

namespace Modules\AdminChat\Tests\Feature;

use App\Models\User;
use Modules\AdminChat\Models\Chat;
use Tests\ModuleTestCase;

class AdminChatSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'AdminChat';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Chat::query()->delete();

        $this->admin = User::factory()->create(['level' => User::ADMIN]);
    }

    public function testIndexIsOpenForAdmin(): void
    {
        $this->actingAs($this->admin)->get('/admin/chats')->assertOk();
    }

    public function testUserIsRejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/chats')->assertForbidden();
    }

    public function testMessageIsPosted(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/chats', ['msg' => 'Сообщение администрации'])
            ->assertRedirect('admin/chats');

        $this->assertDatabaseHas('chats', [
            'user_id' => $this->admin->id,
            'text'    => 'Сообщение администрации',
        ]);
    }

    public function testShortMessageIsRejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/chats', ['msg' => 'Ок'])
            ->assertSessionHasErrors('msg');

        $this->assertDatabaseCount('chats', 0);
    }

    public function testOwnMessageIsEdited(): void
    {
        $post = $this->message();

        $this->actingAs($this->admin)
            ->post('/admin/chats/edit/' . $post->id, ['msg' => 'Исправленное сообщение'])
            ->assertRedirect();

        $this->assertSame('Исправленное сообщение', $post->fresh()->text);
    }

    public function testForeignMessageIsProtected(): void
    {
        $other = User::factory()->create(['level' => User::ADMIN]);
        $post = $this->message(['user_id' => $other->id]);

        $this->actingAs($this->admin)
            ->get('/admin/chats/edit/' . $post->id)
            ->assertSee(__('main.message_deleted'));
    }

    public function testOnlyBossClearsChat(): void
    {
        $this->message();

        $this->actingAs($this->admin)->post('/admin/chats/clear');
        $this->assertDatabaseCount('chats', 1);

        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($boss)->post('/admin/chats/clear')->assertRedirect();
        $this->assertDatabaseCount('chats', 0);
    }

    private function message(array $attributes = []): Chat
    {
        return Chat::query()->create([
            'user_id'    => $this->admin->id,
            'text'       => 'Сообщение администрации',
            'created_at' => now(),
            ...$attributes,
        ]);
    }
}
