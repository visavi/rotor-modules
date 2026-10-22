<?php

namespace Modules\Guestbook\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Guestbook\Models\Guestbook;
use Tests\ModuleTestCase;

class GuestbookTest extends ModuleTestCase
{
    protected string $moduleName = 'Guestbook';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Guestbook::$morphName => Guestbook::class]);

        $this->overrideSetting('guestbook_text_min', 5);
        $this->overrideSetting('guestbook_text_max', 1000);
        $this->overrideSetting('guestbook_point', 0);
        $this->overrideSetting('guestbook_money', 0);
        $this->overrideSetting('guest_moderation', 0);
        $this->overrideSetting('bookadds', 1);
        $this->overrideSetting('captcha_type', 'graphical');
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create();
    }

    public function testUserPostIsAdded(): void
    {
        $this->actingAs($this->user)
            ->post('/guestbook/create', ['text' => '<p>Сообщение в гостевой</p>'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('guestbook', [
            'user_id' => $this->user->id,
            'text'    => '<p>Сообщение в гостевой</p>',
            'active'  => 1,
        ]);
    }

    public function testUserPostTakesPendingFiles(): void
    {
        $file = File::factory()->create([
            'relate_type' => Guestbook::$morphName,
            'relate_id'   => 0,
            'user_id'     => $this->user->id,
            'path'        => '/uploads/guestbook/test.jpg',
            'name'        => 'test.jpg',
            'size'        => 100,
            'extension'   => 'jpg',
        ]);

        $this->actingAs($this->user)
            ->post('/guestbook/create', ['text' => 'Сообщение с картинкой'])
            ->assertSessionHas('success');

        $this->assertSame(Guestbook::query()->value('id'), $file->fresh()->relate_id);
    }

    public function testShortTextShowsError(): void
    {
        $this->actingAs($this->user)
            ->post('/guestbook/create', ['text' => '<p><b>Ок</b></p>'])
            ->assertRedirect(route('guestbook.index'))
            ->assertSessionHasErrors('text');

        $this->assertDatabaseCount('guestbook', 0);
    }

    public function testGuestPostsWithCaptcha(): void
    {
        $this->withSession(['protect' => 'abc12'])
            ->post('/guestbook/create', [
                'text'       => "Привет\nот гостя",
                'guest_name' => 'Гость',
                'protect'    => 'abc12',
            ])
            ->assertSessionHas('success')
            ->assertCookie('guest_name', 'Гость');

        $this->assertDatabaseHas('guestbook', [
            'user_id'    => null,
            'guest_name' => 'Гость',
            'text'       => '<p>Привет<br />' . "\n" . 'от гостя</p>',
        ]);
    }

    public function testGuestNeedsCaptcha(): void
    {
        $this->post('/guestbook/create', ['text' => 'Сообщение от гостя', 'protect' => 'abc12'])
            ->assertRedirect(route('guestbook.index'))
            ->assertSessionHasErrors('protect');

        $this->assertDatabaseCount('guestbook', 0);
    }

    public function testGuestLinksAreRejected(): void
    {
        $this->withSession(['protect' => 'abc12'])
            ->post('/guestbook/create', ['text' => 'Заходите на https://spam.example', 'protect' => 'abc12'])
            ->assertSessionHasErrors('text');

        $this->assertDatabaseCount('guestbook', 0);
    }

    public function testGuestIsBlockedWhenClosed(): void
    {
        $this->overrideSetting('bookadds', 0);

        $this->withSession(['protect' => 'abc12'])
            ->post('/guestbook/create', ['text' => 'Сообщение от гостя', 'protect' => 'abc12'])
            ->assertForbidden();

        $this->assertDatabaseCount('guestbook', 0);
    }

    public function testOwnPostIsEdited(): void
    {
        $post = $this->createPost();

        $this->actingAs($this->user)
            ->get('/guestbook/' . $post->id . '/edit')
            ->assertOk()
            ->assertSee('Сообщение в гостевой');

        $this->actingAs($this->user)
            ->post('/guestbook/' . $post->id . '/edit', ['text' => 'Исправленное сообщение'])
            ->assertSessionHas('success');

        $this->assertSame('Исправленное сообщение', $post->fresh()->text);
        $this->assertSame($this->user->id, $post->fresh()->edit_user_id);
    }

    public function testOldPostIsNotEdited(): void
    {
        $post = $this->createPost(['created_at' => now()->subHour()]);

        $this->actingAs($this->user)
            ->post('/guestbook/' . $post->id . '/edit', ['text' => 'Исправленное сообщение'])
            ->assertSee(__('main.editing_impossible'));

        $this->assertSame('Сообщение в гостевой', $post->fresh()->text);
    }

    public function testGuestCannotEdit(): void
    {
        $post = $this->createPost();

        $this->get('/guestbook/' . $post->id . '/edit')->assertForbidden();
    }

    public function testAdminEditsPost(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);
        $post = $this->createPost(['created_at' => now()->subHour()]);

        $this->actingAs($admin)
            ->post('/admin/guestbook/' . $post->id . '/edit?page=2', ['text' => 'Правка администрации'])
            ->assertRedirect(route('admin.guestbook.index', ['page' => 2]));

        $this->assertSame('Правка администрации', $post->fresh()->text);
    }

    public function testAdminReplyErrorIsShownUnderReply(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);
        $post = $this->createPost();

        $this->actingAs($admin)
            ->post('/admin/guestbook/' . $post->id . '/reply', ['reply' => 'Ок'])
            ->assertSessionHasErrors('reply');

        $this->actingAs($admin)
            ->post('/admin/guestbook/' . $post->id . '/reply?page=3', ['reply' => 'Спасибо за отзыв'])
            ->assertRedirect(route('admin.guestbook.index', ['page' => 3]));

        $this->assertSame('Спасибо за отзыв', $post->fresh()->reply);
    }

    public function testAdminPublishesAndDeletesPosts(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);
        $hidden = $this->createPost(['active' => 0]);
        $other = $this->createPost();

        $this->actingAs($admin)
            ->post('/admin/guestbook/publish', ['chosen' => [$hidden->id]])
            ->assertSessionHas('success');

        $this->assertSame(1, (int) $hidden->fresh()->active);

        $this->actingAs($admin)
            ->post('/admin/guestbook/delete', ['chosen' => []])
            ->assertSessionHasErrors();

        $this->actingAs($admin)
            ->post('/admin/guestbook/delete', ['chosen' => [$hidden->id]])
            ->assertSessionHas('success');

        $this->assertNull($hidden->fresh());
        $this->assertNotNull($other->fresh());
    }

    public function testOnlyBossClearsGuestbookWithFiles(): void
    {
        $post = $this->createPost();
        $file = File::factory()->create([
            'relate_type' => Guestbook::$morphName,
            'relate_id'   => $post->id,
            'user_id'     => $this->user->id,
            'path'        => '/uploads/guestbook/test.jpg',
            'name'        => 'test.jpg',
            'size'        => 100,
            'extension'   => 'jpg',
        ]);

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->post('/admin/guestbook/clear')
            ->assertForbidden();

        $this->assertNotNull($post->fresh());

        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->post('/admin/guestbook/clear')
            ->assertSessionHas('success');

        $this->assertDatabaseCount('guestbook', 0);
        $this->assertNull($file->fresh());
    }

    private function createPost(array $attributes = []): Guestbook
    {
        return Guestbook::factory()->create($attributes + [
            'user_id'    => $this->user->id,
            'text'       => 'Сообщение в гостевой',
            'ip'         => '127.0.0.1',
            'brow'       => 'Chrome',
            'active'     => 1,
            'created_at' => now(),
        ]);
    }
}
