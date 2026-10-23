<?php

namespace Modules\AdminChat\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\AdminChat\Models\Chat;
use Tests\ModuleTestCase;

class AdminChatSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'AdminChat';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function testEditFormOpens(): void
    {
        $post = $this->message();

        $this->actingAs($this->admin)
            ->get('/admin/chats/edit/' . $post->id . '?page=2')
            ->assertOk()
            ->assertSee('Сообщение администрации');
    }

    public function testEditReturnsToPage(): void
    {
        $post = $this->message();

        $this->actingAs($this->admin)
            ->post('/admin/chats/edit/' . $post->id . '?page=2', ['msg' => 'Исправленное сообщение'])
            ->assertRedirect('admin/chats?page=2');
    }

    public function testShortEditIsRejected(): void
    {
        $post = $this->message();
        $form = '/admin/chats/edit/' . $post->id . '?page=2';

        $this->actingAs($this->admin)
            ->from($form)
            ->post('/admin/chats/edit/' . $post->id, ['msg' => '<p>Ок</p>'])
            ->assertRedirect($form)
            ->assertSessionHasErrors('msg');

        $this->assertSame('Сообщение администрации', $post->fresh()->text);
    }

    public function testOldMessageIsNotEdited(): void
    {
        $post = $this->message(['created_at' => now()->subMinutes(11)]);

        $this->actingAs($this->admin)
            ->post('/admin/chats/edit/' . $post->id, ['msg' => 'Исправленное сообщение'])
            ->assertSee(__('main.editing_impossible'));

        $this->assertSame('Сообщение администрации', $post->fresh()->text);
    }

    public function testNextOwnMessageIsAppended(): void
    {
        $post = $this->message();

        $this->actingAs($this->admin)
            ->post('/admin/chats', ['msg' => 'Продолжение'])
            ->assertRedirect('admin/chats');

        $this->assertDatabaseCount('chats', 1);
        $this->assertSame('Сообщение администрации' . PHP_EOL . 'Продолжение', $post->fresh()->text);
    }

    public function testForeignMessageIsProtected(): void
    {
        $other = User::factory()->create(['level' => User::ADMIN]);
        $post = $this->message(['user_id' => $other->id]);

        $this->actingAs($this->admin)
            ->get('/admin/chats/edit/' . $post->id)
            ->assertForbidden()
            ->assertSee(__('ajax.record_not_author'));
    }

    public function testOnlyBossClearsChat(): void
    {
        $this->message();

        $this->actingAs($this->admin)->post('/admin/chats/clear')->assertForbidden();
        $this->assertDatabaseCount('chats', 1);

        $boss = User::factory()->create(['level' => User::BOSS]);
        Cache::put('statChat', 1, 3600);

        $this->actingAs($boss)->post('/admin/chats/clear')->assertRedirect();
        $this->assertDatabaseCount('chats', 0);
        // Массовое удаление событий не вызывает: счётчик в меню сбрасывается вручную
        $this->assertFalse(Cache::has('statChat'));
    }

    private function message(array $attributes = []): Chat
    {
        return Chat::factory()->create([
            'user_id'    => $this->admin->id,
            'text'       => 'Сообщение администрации',
            'ip'         => '127.0.0.1',
            'brow'       => 'Test',
            'created_at' => now(),
            ...$attributes,
        ]);
    }
}
