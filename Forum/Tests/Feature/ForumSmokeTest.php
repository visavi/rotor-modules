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

    public function testPidRedirectsToPostPage(): void
    {
        $this->overrideSetting('forumpost', 2);

        [$topic, $posts] = $this->createTopicWithPosts(3);

        $this->get(route('topics.topic', ['id' => $topic->id, 'pid' => $posts[2]->id]))
            ->assertRedirect(route('topics.topic', ['id' => $topic->id, 'page' => 2]) . '#post_' . $posts[2]->id);

        $this->get(route('topics.topic', ['id' => $topic->id, 'pid' => $posts[1]->id]))
            ->assertRedirect(route('topics.topic', ['id' => $topic->id]) . '#post_' . $posts[1]->id);
    }

    public function testViewUrlPointsToLastPageWithoutPid(): void
    {
        // Ссылка на последнее сообщение строится по счётчику — без редиректа через pid
        $this->overrideSetting('forumpost', 2);

        [$topic, $posts] = $this->createTopicWithPosts(3);

        $this->assertSame(
            route('topics.topic', ['id' => $topic->id, 'page' => 2]) . '#post_' . $posts[2]->id,
            $topic->fresh()->getViewUrl(),
        );
    }

    public function testTopicPageCanonicalKeepsPage(): void
    {
        // Без номера страницы страницы 2+ выглядят для поисковика дублями первой
        $this->overrideSetting('forumpost', 2);

        [$topic] = $this->createTopicWithPosts(3);

        $this->get(route('topics.topic', ['id' => $topic->id, 'page' => 2, 'foo' => 'bar']))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="' . route('topics.topic', ['id' => $topic->id]) . '?page=2">', false);

        $this->get(route('topics.topic', ['id' => $topic->id, 'page' => 1]))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="' . route('topics.topic', ['id' => $topic->id]) . '">', false);
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

    public function testTopicEditShowsPostFiles(): void
    {
        // Редактор первого сообщения грузит файлы прямо в него — без списка
        // их не было видно и нечем было удалить
        $this->overrideSetting('editforumpoint', 0);

        $forum = Forum::query()->create(['title' => 'Test forum']);

        $topic = Topic::query()->create([
            'forum_id'    => $forum->id,
            'title'       => 'Test topic',
            'user_id'     => $this->user->id,
            'count_posts' => 1,
            'created_at'  => now(),
        ]);

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'user_id'  => $this->user->id,
            'text'     => 'Первое сообщение',
            'ip'       => '127.0.0.1',
            'brow'     => 'test',
        ]);

        $file = File::query()->create([
            'relate_id'   => $post->id,
            'relate_type' => Post::$morphName,
            'path'        => '/uploads/forums/doc.pdf',
            'name'        => 'doc.pdf',
            'size'        => 1024,
            'extension'   => 'pdf',
            'mime_type'   => 'application/pdf',
            'user_id'     => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('topics.edit', ['id' => $topic->id]))
            ->assertOk()
            ->assertSee('data-key="' . $file->id . '"', false);
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

    public function testApiPostKeepsPendingFilesFirst(): void
    {
        // Загруженные заранее идут первыми, файлы из запроса — после них:
        // в обратном порядке sort групп совпадал и вложения перемешивались
        $this->overrideSetting('forum_text_min', 1);
        $this->overrideSetting('forum_text_max', 1000);
        $this->overrideSetting('file_extensions', 'pdf,txt');
        $this->overrideSetting('filesize', 1024 * 1024);
        $this->overrideSetting('maxfiles', 5);

        $forum = Forum::query()->create(['title' => 'Test forum']);

        $topic = Topic::query()->create([
            'forum_id'    => $forum->id,
            'title'       => 'Test topic',
            'user_id'     => $this->user->id,
            'count_posts' => 0,
            'created_at'  => now(),
        ]);

        foreach (['first.pdf', 'second.pdf'] as $i => $name) {
            File::query()->create([
                'relate_id'   => 0,
                'relate_type' => Post::$morphName,
                'path'        => '/uploads/forums/' . $name,
                'name'        => $name,
                'size'        => 1024,
                'extension'   => 'pdf',
                'mime_type'   => 'application/pdf',
                'user_id'     => $this->user->id,
                'sort'        => $i + 1,
            ]);
        }

        $this->user->update(['apikey' => Str::random(32)]);

        $id = $this->post('/api/topics/' . $topic->id, [
            'text'  => 'Сообщение',
            'files' => [UploadedFile::fake()->createWithContent('third.txt', 'text')],
        ], ['Authorization' => 'Bearer ' . $this->user->apikey, 'Accept' => 'application/json'])
            ->assertStatus(201)
            ->json('post.id');

        $files = Post::query()->find($id)->files()->ordered()->get();

        try {
            $this->assertSame(['first.pdf', 'second.pdf', 'third.txt'], $files->pluck('name')->all());
        } finally {
            // Файл из запроса реально лёг в public/uploads
            if ($uploaded = $files->firstWhere('name', 'third.txt')) {
                @unlink(public_path($uploaded->path));
            }
        }
    }

    /**
     * @return array{Topic, list<Post>}
     */
    private function createTopicWithPosts(int $count): array
    {
        $forum = Forum::query()->create(['title' => 'Test forum']);

        $topic = Topic::query()->create([
            'forum_id'    => $forum->id,
            'title'       => 'Test topic',
            'user_id'     => $this->user->id,
            'count_posts' => 0,
            'created_at'  => now(),
        ]);

        $posts = [];

        foreach (range(1, $count) as $i) {
            $posts[] = Post::query()->create([
                'topic_id'   => $topic->id,
                'user_id'    => $this->user->id,
                'text'       => 'Сообщение ' . $i,
                'ip'         => '127.0.0.1',
                'brow'       => 'test',
                'created_at' => now()->addSeconds($i),
            ]);
        }

        $topic->update(['count_posts' => $count, 'last_post_id' => end($posts)->id]);

        return [$topic, $posts];
    }

    public function testUserPagesAreClosedForGuests(): void
    {
        // Страницы пользователя только авторизованным — гостю 403
        $routes = ['forums.active-topics', 'forums.active-posts'];

        foreach ($routes as $route) {
            $this->get(route($route, ['user' => $this->user->login]))->assertForbidden();
        }

        $this->actingAs($this->user);

        foreach ($routes as $route) {
            $this->get(route($route, ['user' => $this->user->login]))->assertOk();
        }
    }
}
