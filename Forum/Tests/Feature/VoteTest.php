<?php

namespace Modules\Forum\Tests\Feature;

use App\Models\Feed;
use App\Models\User;
use App\Services\FeedService;
use App\Support\Registry;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Models\Vote;
use Modules\Forum\Models\VoteAnswer;
use Tests\ModuleTestCase;

class VoteTest extends ModuleTestCase
{
    protected string $moduleName = 'Forum';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([
            Topic::$morphName => Topic::class,
            Post::$morphName  => Post::class,
            Vote::$morphName  => Vote::class,
        ]);

        // Ленту регистрирует ModuleServiceProvider, в тестах модуль поднимается вручную
        $config = require base_path('modules/Forum/module.php');
        Registry::feed(Topic::class, $config['models'][Topic::class]['feed']);

        $this->overrideSetting('feed_topics_show', 1);
        $this->overrideSetting('feed_topics_rating', 0);
        $this->overrideSetting('feed_per_page', 20);
        $this->overrideSetting('feed_cache_time', 60);

        $this->user = User::factory()->create();
    }

    public function testFeedShowsVoteForm(): void
    {
        [$topic] = $this->createTopicWithVote();

        $this->actingAs($this->user);
        $feed = (string) (new FeedService())->getFeed();

        $this->assertStringContainsString('Любимый цвет?', $feed);
        $this->assertStringContainsString('Красный', $feed);
        $this->assertStringContainsString(route('topics.vote', ['id' => $topic->id]), $feed);
    }

    public function testFeedHidesVoteFromGuest(): void
    {
        $this->createTopicWithVote();

        $feed = (string) (new FeedService())->getFeed();

        $this->assertStringContainsString('Test topic', $feed);
        $this->assertStringNotContainsString('Любимый цвет?', $feed);
    }

    public function testFeedHidesVoteAfterUserVoted(): void
    {
        [$topic, , $answers] = $this->createTopicWithVote();

        $this->actingAs($this->user)
            ->post(route('topics.vote', ['id' => $topic->id]), ['poll' => $answers[0]->id], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJsonPath('success', true);

        // Тема всплывает в ленте с каждым ответом, повторно опрос показывать незачем
        $this->actingAs($this->user);
        $feed = (string) (new FeedService())->getFeed();

        $this->assertStringContainsString('Test topic', $feed);
        $this->assertStringNotContainsString('Любимый цвет?', $feed);
    }

    public function testAjaxVoteReturnsResults(): void
    {
        [$topic, $vote, $answers] = $this->createTopicWithVote();

        $response = $this->actingAs($this->user)
            ->post(route('topics.vote', ['id' => $topic->id]), ['poll' => $answers[0]->id], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertStringContainsString(__('forum::forums.total_votes') . ': 1', $response->json('html'));
        // После голоса вместо формы приходят результаты
        $this->assertStringNotContainsString('type="radio"', $response->json('html'));

        $this->assertSame(1, $vote->fresh()->count);
        $this->assertSame(1, $answers[0]->fresh()->result);
    }

    public function testAjaxVoteTwiceIsRejected(): void
    {
        [$topic, , $answers] = $this->createTopicWithVote();

        $this->actingAs($this->user)
            ->post(route('topics.vote', ['id' => $topic->id]), ['poll' => $answers[0]->id], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJsonPath('success', true);

        $this->actingAs($this->user)
            ->post(route('topics.vote', ['id' => $topic->id]), ['poll' => $answers[0]->id], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('forum::forums.vote_passed'));
    }

    public function testFeedCacheDoesNotLeakVoteOfAnotherUser(): void
    {
        [$topic, , $answers] = $this->createTopicWithVote();

        $this->actingAs($this->user)
            ->post(route('topics.vote', ['id' => $topic->id]), ['poll' => $answers[0]->id], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJsonPath('success', true);

        // Первым лента прогревается проголосовавшим, вторым её видит другой пользователь
        $this->actingAs($this->user);
        (new FeedService())->getFeed();

        $other = User::factory()->create();
        $this->actingAs($other);
        $feed = (string) (new FeedService())->getFeed();

        $this->assertStringContainsString('type="radio"', $feed);
    }

    /**
     * @return array{0: Topic, 1: Vote, 2: array<int, VoteAnswer>}
     */
    private function createTopicWithVote(): array
    {
        $forum = Forum::query()->create(['title' => 'Test forum']);

        $topic = Topic::query()->create([
            'forum_id'   => $forum->id,
            'title'      => 'Test topic',
            'user_id'    => $this->user->id,
            'created_at' => now(),
        ]);

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'user_id'  => $this->user->id,
            'text'     => 'Последнее сообщение темы',
            'ip'       => '127.0.0.1',
            'brow'     => 'test',
        ]);

        $topic->update(['last_post_id' => $post->id]);

        $vote = Vote::query()->create([
            'topic_id'   => $topic->id,
            'title'      => 'Любимый цвет?',
            'count'      => 0,
            'created_at' => now(),
        ]);

        $answers = [
            VoteAnswer::query()->create(['vote_id' => $vote->id, 'answer' => 'Красный', 'result' => 0]),
            VoteAnswer::query()->create(['vote_id' => $vote->id, 'answer' => 'Зелёный', 'result' => 0]),
        ];

        Feed::query()->updateOrInsert([
            'relate_type' => Topic::$morphName,
            'relate_id'   => $topic->id,
        ], [
            'created_at' => $topic->created_at,
        ]);

        return [$topic, $vote, $answers];
    }
}
