<?php

namespace Modules\Forum\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Tests\ModuleTestCase;

class ForumSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Forum';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([
            Topic::$morphName => Topic::class,
            Post::$morphName  => Post::class,
        ]);

        $this->user = User::factory()->create();
    }

    public function testIndex(): void
    {
        $this->get(route('forums.index'))->assertOk();
    }

    public function testForum(): void
    {
        $forum = Forum::query()->create(['title' => 'Test forum']);

        $this->get(route('forums.forum', ['id' => $forum->id]))->assertOk();
    }

    public function testTopic(): void
    {
        $forum = Forum::query()->create(['title' => 'Test forum']);

        $topic = Topic::query()->create([
            'forum_id'    => $forum->id,
            'title'       => 'Test topic',
            'user_id'     => $this->user->id,
            'count_posts' => 0,
            'created_at'  => now()->timestamp,
        ]);

        $this->get(route('topics.topic', ['id' => $topic->id]))->assertOk();
    }

    public function testApiTopicContainsForum(): void
    {
        $forum = Forum::query()->create(['title' => 'Test forum']);

        $topic = Topic::query()->create([
            'forum_id'    => $forum->id,
            'title'       => 'Test topic',
            'user_id'     => $this->user->id,
            'count_posts' => 0,
            'created_at'  => now(),
        ]);

        $this->user->update(['apikey' => Str::random(32)]);

        $this->getJson('/api/topics/' . $topic->id, ['Authorization' => 'Bearer ' . $this->user->apikey])
            ->assertOk()
            ->assertJsonPath('topic.forum_id', $forum->id)
            ->assertJsonPath('topic.forum.id', $forum->id)
            ->assertJsonPath('topic.forum.title', 'Test forum')
            // У корневого раздела родителя нет
            ->assertJsonPath('topic.forum.parent', null);
    }

    public function testApiReadingIsOpenForGuests(): void
    {
        $forum = Forum::query()->create(['title' => 'Test forum']);

        $topic = Topic::query()->create([
            'forum_id'    => $forum->id,
            'title'       => 'Test topic',
            'user_id'     => $this->user->id,
            'count_posts' => 0,
            'created_at'  => now(),
        ]);

        $this->getJson('/api/forums')->assertOk();
        $this->getJson('/api/forums/' . $forum->id)->assertOk();
        $this->getJson('/api/topics/' . $topic->id)->assertOk();
    }

    public function testApiWritingRequiresToken(): void
    {
        $forum = Forum::query()->create(['title' => 'Test forum']);

        $this->postJson('/api/forums/' . $forum->id, ['title' => 'New topic', 'text' => 'Text'])
            ->assertStatus(400);
    }

    public function testApiRejectsOversizedFile(): void
    {
        // filesize хранится в байтах, а правило max у Laravel считает килобайты:
        // переданная напрямую настройка пропускала файлы в 1024 раза больше
        $this->overrideSetting('filesize', 100 * 1024);
        $this->overrideSetting('file_extensions', 'pdf');
        $this->overrideSetting('forum_text_min', 1);
        $this->overrideSetting('forum_text_max', 1000);

        $forum = Forum::query()->create(['title' => 'Test forum']);

        $topic = Topic::query()->create([
            'forum_id'    => $forum->id,
            'title'       => 'Test topic',
            'user_id'     => $this->user->id,
            'count_posts' => 0,
            'created_at'  => now(),
        ]);

        $this->user->update(['apikey' => Str::random(32)]);

        $this->post('/api/topics/' . $topic->id, [
            'text'  => 'Сообщение с файлом',
            'files' => [UploadedFile::fake()->create('big.pdf', 200, 'application/pdf')],
        ], ['Authorization' => 'Bearer ' . $this->user->apikey, 'Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('files.0');
    }

    public function testApiPostAttachesPendingFiles(): void
    {
        // Файл, загруженный заранее через POST /api/files, ждёт записи с relate_id = 0
        $this->overrideSetting('forum_text_min', 1);
        $this->overrideSetting('forum_text_max', 1000);

        $forum = Forum::query()->create(['title' => 'Test forum']);

        $topic = Topic::query()->create([
            'forum_id'    => $forum->id,
            'title'       => 'Test topic',
            'user_id'     => $this->user->id,
            'count_posts' => 0,
            'created_at'  => now(),
        ]);

        $file = File::query()->create([
            'relate_id'   => 0,
            'relate_type' => Post::$morphName,
            'path'        => '/uploads/forums/doc.pdf',
            'name'        => 'doc.pdf',
            'size'        => 1024,
            'extension'   => 'pdf',
            'mime_type'   => 'application/pdf',
            'user_id'     => $this->user->id,
        ]);

        $this->user->update(['apikey' => Str::random(32)]);

        $id = $this->postJson('/api/topics/' . $topic->id, ['text' => 'Сообщение'], ['Authorization' => 'Bearer ' . $this->user->apikey])
            ->assertStatus(201)
            ->json('post.id');

        $this->assertSame($id, $file->fresh()->relate_id);
    }
}
