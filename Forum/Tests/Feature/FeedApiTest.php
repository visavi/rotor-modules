<?php

namespace Modules\Forum\Tests\Feature;

use App\Classes\Registry;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Tests\ModuleTestCase;

class FeedApiTest extends ModuleTestCase
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

        // Ленту регистрирует ModuleServiceProvider, в тестах модуль поднимается вручную
        $config = require base_path('modules/Forum/module.php');
        Registry::feed(Topic::class, $config['models'][Topic::class]['feed']);

        Registry::ratingType(Post::$morphName);

        $this->overrideSetting('feed_topics_show', 1);
        $this->overrideSetting('feed_topics_rating', 0);
        $this->overrideSetting('feed_per_page', 20);
        $this->overrideSetting('feed_cache_time', 0);

        $this->user = User::factory()->create();
    }

    public function testFeedReturnsTopicWithLastPostData(): void
    {
        [$topic, $post] = $this->createTopicInFeed();

        $response = $this->get('/api/feed');

        $response->assertOk();
        $response->assertJsonPath('data.0.type', Topic::$morphName);
        $response->assertJsonPath('data.0.id', $topic->id);
        $response->assertJsonPath('data.0.title', 'Test topic');
        $response->assertJsonPath('data.0.url', route('topics.topic', ['id' => $topic->id, 'pid' => $post->id]));
        $response->assertJsonPath('data.0.comments_count', 1);
        $response->assertJsonPath('data.0.user.login', $this->user->login);
        $this->assertStringContainsString('Последнее сообщение темы', $response->json('data.0.text'));
    }

    public function testFeedVoteIsNullForGuest(): void
    {
        [, $post] = $this->createTopicInFeed();

        $response = $this->get('/api/feed');

        $response->assertOk();
        // Цель голосования в теме — последнее сообщение
        $response->assertJsonPath('data.0.vote.type', Post::$morphName);
        $response->assertJsonPath('data.0.vote.id', $post->id);
        $response->assertJsonPath('data.0.vote.value', null);
    }

    public function testFeedReturnsVoteOfCurrentUser(): void
    {
        [, $post] = $this->createTopicInFeed();

        $voter = User::factory()->create(['apikey' => Str::random(32)]);

        $this->postJson('/api/rating', [
            'type' => Post::$morphName,
            'id'   => $post->id,
            'vote' => '+',
        ], ['Authorization' => 'Bearer ' . $voter->apikey])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('rating', 1);

        $this->getJson('/api/feed', ['Authorization' => 'Bearer ' . $voter->apikey])
            ->assertOk()
            ->assertJsonPath('data.0.vote.value', '+')
            ->assertJsonPath('data.0.vote.own', false);
    }

    public function testRatingRequiresToken(): void
    {
        [, $post] = $this->createTopicInFeed();

        $this->postJson('/api/rating', [
            'type' => Post::$morphName,
            'id'   => $post->id,
            'vote' => '+',
        ])->assertStatus(400);
    }

    /**
     * @return array{0: Topic, 1: Post}
     */
    private function createTopicInFeed(): array
    {
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
            'text'     => 'Последнее сообщение темы',
            'ip'       => '127.0.0.1',
            'brow'     => 'test',
        ]);

        $topic->update(['last_post_id' => $post->id]);

        Feed::query()->insert([
            'relate_type' => Topic::$morphName,
            'relate_id'   => $topic->id,
            'created_at'  => $topic->created_at,
        ]);

        return [$topic, $post];
    }
}
