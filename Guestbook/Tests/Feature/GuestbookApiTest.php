<?php

namespace Modules\Guestbook\Tests\Feature;

use App\Models\File;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Guestbook\Models\Guestbook;
use Tests\ModuleTestCase;

class GuestbookApiTest extends ModuleTestCase
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
        $this->overrideSetting('filesize', 10485760);
        $this->overrideSetting('media_extensions', 'jpg,png');
        $this->overrideSetting('maxfiles', 5);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
    }

    public function testIndexIsOpenForGuests(): void
    {
        $this->createPost();

        $this->getJson('/api/guestbook')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'text', 'user', 'created_at']], 'links', 'meta']);
    }

    public function testUnpublishedPostIsHidden(): void
    {
        $this->createPost(['active' => 0]);

        $this->getJson('/api/guestbook')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function testStoreCreatesPost(): void
    {
        $this->postJson('/api/guestbook', ['text' => 'Сообщение в гостевую'], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('post.text', 'Сообщение в гостевую')
            ->assertJsonPath('post.user.login', $this->user->login);

        $this->assertDatabaseHas('guestbook', ['user_id' => $this->user->id, 'active' => 1]);
    }

    public function testStoreAcceptsMediaInRequest(): void
    {
        $this->post('/api/guestbook', [
            'text'  => 'Сообщение с картинкой',
            'files' => [UploadedFile::fake()->image('screen.jpg', 300, 300)],
        ], $this->headers())
            ->assertStatus(201)
            ->assertJsonCount(1, 'post.media');
    }

    public function testStoreAttachesPendingFiles(): void
    {
        $file = File::query()->create([
            'relate_id'   => 0,
            'relate_type' => Guestbook::$morphName,
            'path'        => '/uploads/guestbook/screen.jpg',
            'name'        => 'screen.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);

        $id = $this->postJson('/api/guestbook', ['text' => 'Сообщение с вложением'], $this->headers())
            ->json('post.id');

        $this->assertSame($id, $file->fresh()->relate_id);
    }

    public function testShortTextIsRejected(): void
    {
        $this->postJson('/api/guestbook', ['text' => 'Ок'], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('text');
    }

    public function testGuestNeedsCaptcha(): void
    {
        $this->postJson('/api/guestbook', ['text' => 'Сообщение от гостя', 'guest_name' => 'Гость'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('text');
    }

    public function testGuestPostsWithCaptcha(): void
    {
        $this->postJson('/api/guestbook', [
            'text'       => 'Сообщение от гостя',
            'guest_name' => 'Гость',
        ] + $this->captcha())
            ->assertStatus(201)
            ->assertJsonPath('post.guest_name', 'Гость')
            ->assertJsonPath('post.user', null);
    }

    public function testGuestLinksAreRejected(): void
    {
        $this->postJson('/api/guestbook', [
            'text' => 'Заходите на https://spam.example',
        ] + $this->captcha())
            ->assertStatus(422)
            ->assertJsonValidationErrors('text');
    }

    public function testGuestIsBlockedWhenClosed(): void
    {
        $this->overrideSetting('bookadds', 0);

        $this->postJson('/api/guestbook', ['text' => 'Сообщение от гостя'] + $this->captcha())
            ->assertStatus(403);
    }

    public function testGuestPostWaitsModeration(): void
    {
        $this->overrideSetting('guest_moderation', 1);

        $id = $this->postJson('/api/guestbook', ['text' => 'Сообщение от гостя'] + $this->captcha())
            ->assertStatus(201)
            ->json('post.id');

        $this->assertDatabaseHas('guestbook', ['id' => $id, 'active' => 0]);
    }

    public function testUpdateOwnPost(): void
    {
        $post = $this->createPost();

        $this->patchJson('/api/guestbook/' . $post->id, ['text' => 'Исправленное сообщение'], $this->headers())
            ->assertOk()
            ->assertJsonPath('post.text', 'Исправленное сообщение');
    }

    public function testOldPostIsNotEditable(): void
    {
        $post = $this->createPost(['created_at' => now()->subHour()]);

        $this->patchJson('/api/guestbook/' . $post->id, ['text' => 'Исправленное сообщение'], $this->headers())
            ->assertStatus(422);
    }

    public function testForeignPostIsProtected(): void
    {
        $post = $this->createPost(['user_id' => User::factory()->create()->id]);

        $this->patchJson('/api/guestbook/' . $post->id, ['text' => 'Исправленное сообщение'], $this->headers())
            ->assertStatus(404);
    }

    public function testModulePublishesLimitsAndCounter(): void
    {
        $this->createPost();

        $this->getJson('/api/config')
            ->assertOk()
            ->assertJsonPath('guestbook.text_min', 5)
            ->assertJsonPath('guestbook.text_max', 1000);

        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('sections.' . Guestbook::$morphName, 1);
    }

    /**
     * Разгаданная капча: ключ и ответ вместо картинки
     */
    private function captcha(): array
    {
        $key = 'captchakey' . Str::random(8);
        Cache::put(CaptchaService::CACHE_PREFIX . $key, 'abc12', CaptchaService::LIFETIME);

        return ['captcha_key' => $key, 'protect' => 'abc12'];
    }

    private function createPost(array $attributes = []): Guestbook
    {
        return Guestbook::query()->create($attributes + [
            'user_id'    => $this->user->id,
            'text'       => 'Сообщение в гостевой',
            'ip'         => '127.0.0.1',
            'brow'       => 'Chrome',
            'active'     => 1,
            'created_at' => now(),
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }
}
