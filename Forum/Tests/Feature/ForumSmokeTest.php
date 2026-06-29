<?php

namespace Modules\Forum\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
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
}
