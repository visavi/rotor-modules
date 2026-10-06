<?php

namespace Modules\Forum\Tests\Feature;

use App\Models\Poll;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Forum\Models\Bookmark;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Tests\ModuleTestCase;

class ForumApiListTest extends ModuleTestCase
{
    protected string $moduleName = 'Forum';

    private User $user;

    private Forum $forum;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([
            Topic::$morphName => Topic::class,
            Post::$morphName  => Post::class,
        ]);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
        $this->forum = Forum::query()->create(['title' => 'Test forum']);
    }

    public function testPostsCarryUserVote(): void
    {
        // Клиенту видно, голосовал ли пользователь, без отдельного запроса
        $topic = $this->createTopic($this->user);
        $post = $this->createPost($topic, User::factory()->create());

        Poll::query()->create([
            'relate_type' => Post::$morphName,
            'relate_id'   => $post->id,
            'user_id'     => $this->user->id,
            'vote'        => '+',
            'created_at'  => now(),
        ]);

        $this->getJson('/api/topics/' . $topic->id, $this->auth())
            ->assertOk()
            ->assertJsonPath('data.0.vote.type', Post::$morphName)
            ->assertJsonPath('data.0.vote.id', $post->id)
            ->assertJsonPath('data.0.vote.value', '+')
            ->assertJsonPath('data.0.vote.own', false);
    }

    public function testGuestSeesPostWithoutVote(): void
    {
        // Отдельный тест: guard авторизации живёт между запросами одного теста
        $topic = $this->createTopic($this->user);
        $post = $this->createPost($topic, User::factory()->create());

        Poll::query()->create([
            'relate_type' => Post::$morphName,
            'relate_id'   => $post->id,
            'user_id'     => $this->user->id,
            'vote'        => '+',
            'created_at'  => now(),
        ]);

        $this->getJson('/api/topics/' . $topic->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vote.value', null);
    }

    public function testNewTopicsAreOrderedByLastPost(): void
    {
        $old = $this->createTopic($this->user, now()->subDay());
        $fresh = $this->createTopic(User::factory()->create(), now());

        $this->getJson('/api/topics')
            ->assertOk()
            ->assertJsonPath('data.0.id', $fresh->id)
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonPath('data.0.forum.title', 'Test forum');
    }

    public function testNewTopicsFilterByUser(): void
    {
        $own = $this->createTopic($this->user);
        $this->createTopic(User::factory()->create());

        // Списки пользователя — только с токеном, как страницы сайта
        $this->getJson('/api/topics?user=' . $this->user->login)->assertForbidden();
        $this->actingAs($this->user);

        $this->getJson('/api/topics?user=' . $this->user->login)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);

        $this->getJson('/api/topics?user=nobody_' . Str::random(8))
            ->assertNotFound();
    }

    public function testNewPostsCarryTopicAndFilterByUser(): void
    {
        $topic = $this->createTopic($this->user);
        $own = $this->createPost($topic, $this->user);
        $other = $this->createPost($topic, User::factory()->create());
        Post::query()->whereKey($own->id)->update(['created_at' => now()->subHour()]);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $other->id)
            ->assertJsonPath('data.0.topic.id', $topic->id)
            ->assertJsonPath('data.0.topic.title', 'Test topic')
            ->assertJsonPath('data.1.id', $own->id);

        // Списки пользователя — только с токеном, как страницы сайта
        $this->getJson('/api/posts?user=' . $this->user->login)->assertForbidden();
        $this->actingAs($this->user);

        $this->getJson('/api/posts?user=' . $this->user->login)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);

        // Внутри темы она известна клиенту — поля нет
        $this->getJson('/api/topics/' . $topic->id)
            ->assertOk()
            ->assertJsonMissingPath('data.0.topic');
    }

    public function testSiteListsRender(): void
    {
        $topic = $this->createTopic($this->user);
        $post = $this->createPost($topic, $this->user);

        $this->get(route('topics.index'))->assertOk()->assertSee(route('topics.topic', ['id' => $topic->id]), false);
        $this->get(route('posts.index'))->assertOk()->assertSee('pid=' . $post->id, false);
    }

    public function testNewTopicsSortAsOnSite(): void
    {
        $quiet = $this->createTopic($this->user, now());
        $busy = $this->createTopic($this->user, now()->subDay());
        $busy->update(['count_posts' => 10]);

        $this->getJson('/api/topics?sort=posts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $busy->id)
            ->assertJsonPath('data.1.id', $quiet->id);
    }

    public function testNewPostsSortByRatingWithinPeriod(): void
    {
        // Лучшие за неделю: старое сообщение с высоким рейтингом в период не попадает
        $topic = $this->createTopic($this->user);
        $old = $this->createPost($topic, $this->user);
        $best = $this->createPost($topic, $this->user);
        $plain = $this->createPost($topic, $this->user);

        Post::query()->whereKey($old->id)->update(['rating' => 100, 'created_at' => now()->subDays(30)]);
        Post::query()->whereKey($best->id)->update(['rating' => 5]);

        $this->getJson('/api/posts?sort=rating&period=7')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $best->id)
            ->assertJsonPath('data.1.id', $plain->id);
    }

    public function testBookmarksShowNewPosts(): void
    {
        $topic = $this->createTopic(User::factory()->create());
        $topic->update(['count_posts' => 5]);
        $this->createTopic(User::factory()->create());

        Bookmark::query()->create([
            'user_id'     => $this->user->id,
            'topic_id'    => $topic->id,
            'count_posts' => 3,
        ]);

        $this->getJson('/api/bookmarks', $this->auth())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $topic->id)
            ->assertJsonPath('data.0.bookmark.count_posts', 3)
            ->assertJsonPath('data.0.bookmark.new_posts', 2);

        // Вне закладок поля нет
        $this->getJson('/api/topics')
            ->assertOk()
            ->assertJsonMissingPath('data.0.bookmark');
    }

    public function testBookmarksRequireToken(): void
    {
        $this->getJson('/api/bookmarks')->assertStatus(400);
    }

    /**
     * @return array<string, string>
     */
    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }

    private function createTopic(User $user, ?DateTimeInterface $updatedAt = null): Topic
    {
        $topic = Topic::query()->create([
            'forum_id'    => $this->forum->id,
            'title'       => 'Test topic',
            'user_id'     => $user->id,
            'count_posts' => 0,
            'created_at'  => now(),
        ]);

        if ($updatedAt) {
            Topic::query()->whereKey($topic->id)->update(['updated_at' => $updatedAt]);
        }

        return $topic;
    }

    private function createPost(Topic $topic, User $user): Post
    {
        return Post::query()->create([
            'topic_id' => $topic->id,
            'user_id'  => $user->id,
            'text'     => 'Сообщение',
            'ip'       => '127.0.0.1',
            'brow'     => 'test',
        ]);
    }
}
