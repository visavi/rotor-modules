<?php

namespace Modules\Forum\Tests\Feature;

use App\Models\File;
use App\Models\User;
use App\Support\Registry;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Models\Vote;
use Modules\Forum\Models\VoteAnswer;
use Tests\ModuleTestCase;

class ForumFormTest extends ModuleTestCase
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

        $this->overrideSetting('forum_title_min', 5);
        $this->overrideSetting('forum_title_max', 50);
        $this->overrideSetting('forum_text_min', 5);
        $this->overrideSetting('forum_text_max', 1000);
        $this->overrideSetting('vote_title_min', 5);
        $this->overrideSetting('vote_title_max', 50);
        $this->overrideSetting('vote_answer_min', 1);
        $this->overrideSetting('vote_answer_max', 50);
        $this->overrideSetting('forum_category_min', 3);
        $this->overrideSetting('forum_category_max', 50);
        $this->overrideSetting('forum_description_min', 0);
        $this->overrideSetting('forum_description_max', 100);
        $this->overrideSetting('forum_note_min', 0);
        $this->overrideSetting('forum_note_max', 250);
        $this->overrideSetting('forum_merge_posts', 0);
        $this->overrideSetting('editforumpoint', 0);
        $this->overrideSetting('forumpost', 10);
        $this->overrideSetting('forumtem', 10);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
        $this->forum = Forum::factory()->create(['title' => 'Общий форум']);
    }

    public function testTopicIsCreatedWithVote(): void
    {
        $this->actingAs($this->user)
            ->get('/forums/create?fid=' . $this->forum->id)
            ->assertOk()
            ->assertSee('name="forum_id"', false);

        $this->actingAs($this->user)
            ->post('/forums/create', [
                'vote'     => 1,
                'question' => 'Какой цвет лучше?',
                'answers'  => ['Красный', '', 'Синий', 'Красный'],
            ] + $this->topicPayload())
            ->assertSessionHas('success');

        $topic = Topic::query()->where('title', 'Новая тема форума')->firstOrFail();
        $vote = Vote::query()->where('topic_id', $topic->id)->firstOrFail();

        $this->assertSame(['Красный', 'Синий'], VoteAnswer::query()->where('vote_id', $vote->id)->pluck('answer')->all());
    }

    public function testVoteWithoutQuestionIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/forums/create', ['vote' => 1, 'question' => '', 'answers' => ['Да', 'Нет']] + $this->topicPayload())
            ->assertSessionHasErrors('question');

        $this->assertDatabaseCount('topics', 0);
    }

    public function testUncheckedVoteIgnoresQuestion(): void
    {
        // Снятый флажок не приходит — ноль за него шлёт скрытое поле формы
        $this->actingAs($this->user)
            ->get('/forums/create')
            ->assertSee('<input type="hidden" name="vote" value="0">', false);

        $this->actingAs($this->user)
            ->post('/forums/create', ['vote' => 0, 'question' => 'Вопрос из скрытой формы', 'answers' => ['Да']] + $this->topicPayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('votes', 0);
    }

    public function testTopicErrorsAreShownUnderTheirField(): void
    {
        $this->forum->update(['closed' => 1]);

        $this->actingAs($this->user)
            ->post('/forums/create', ['text' => '<p><b>Ок</b></p>'] + $this->topicPayload())
            ->assertSessionHasErrors(['forum_id', 'text']);

        $this->assertDatabaseCount('topics', 0);
    }

    public function testTopicFloodIsShownUnderTitle(): void
    {
        $this->overrideSetting('floodstime', 60);

        $this->actingAs($this->user)->post('/forums/create', $this->topicPayload())->assertSessionHasNoErrors();
        $this->actingAs($this->user)->post('/forums/create', $this->topicPayload())->assertSessionHasErrors('title');

        $this->assertDatabaseCount('topics', 1);
    }

    public function testGuestCannotCreateTopic(): void
    {
        $this->get('/forums/create')->assertForbidden();
        $this->post('/forums/create', $this->topicPayload())->assertForbidden();
    }

    public function testReplyIsAdded(): void
    {
        $topic = $this->createTopic();

        $this->actingAs($this->user)
            ->post('/topics/' . $topic->id . '/create', ['text' => '<p>Ответ в тему</p>'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('posts', ['topic_id' => $topic->id, 'text' => '<p>Ответ в тему</p>']);
    }

    public function testReplyErrorsReturnToLastPage(): void
    {
        $topic = $this->createTopic();
        $lastPage = route('topics.topic', ['id' => $topic->id, 'page' => Topic::pageOf($topic->count_posts)]);

        // Повтор последнего сообщения
        $this->actingAs($this->user)
            ->post('/topics/' . $topic->id . '/create', ['text' => 'Первое сообщение темы'])
            ->assertRedirect($lastPage)
            ->assertSessionHasErrors('text');

        $topic->update(['closed' => 1]);

        $this->actingAs($this->user)
            ->post('/topics/' . $topic->id . '/create', ['text' => 'Ответ в закрытую тему'])
            ->assertSessionHasErrors(['text' => __('forum::forums.topic_closed')]);

        $this->assertDatabaseCount('posts', 1);
    }

    public function testAuthorEditsTopicAndVote(): void
    {
        $topic = $this->createTopic();
        $vote = Vote::factory()->create(['title' => 'Старый вопрос', 'topic_id' => $topic->id]);
        $answer = VoteAnswer::factory()->create(['vote_id' => $vote->id, 'answer' => 'Да']);
        VoteAnswer::factory()->create(['vote_id' => $vote->id, 'answer' => 'Нет']);

        $this->actingAs($this->user)
            ->get('/topics/' . $topic->id . '/edit')
            ->assertOk();

        $this->actingAs($this->user)
            ->post('/topics/' . $topic->id . '/edit', [
                'title'    => 'Исправленная тема',
                'text'     => 'Исправленное сообщение',
                'question' => 'Новый вопрос опроса',
                'answers'  => [$answer->id => 'Конечно', 'new' => 'Не знаю'],
            ])
            ->assertRedirect(route('topics.topic', ['id' => $topic->id]));

        $this->assertSame('Исправленная тема', $topic->fresh()->title);
        $this->assertSame('Новый вопрос опроса', $vote->fresh()->title);
        $this->assertSame(['Конечно', 'Не знаю'], $vote->answers()->pluck('answer')->all());
    }

    public function testRepeatWithFilteredWordIsCaught(): void
    {
        $topic = $this->createTopic();
        Post::query()->where('topic_id', $topic->id)->update(['text' => '<p>Повтор с плохим словом</p>']);

        // Фильтр вывода (антимат) меняет атрибут, а повтор сравнивается с текстом из базы
        $filters = Registry::$textFilters;
        Registry::textFilter(static fn (string $text): string => str_replace('плохим', '***', $text));

        try {
            $this->actingAs($this->user)
                ->post('/topics/' . $topic->id . '/create', ['text' => '<p>Повтор с плохим словом</p>'])
                ->assertSessionHasErrors(['text' => __('forum::forums.post_repeat')]);
        } finally {
            Registry::$textFilters = $filters;
        }
    }

    public function testMergeCountsFilesFromRequest(): void
    {
        $this->overrideSetting('forum_merge_posts', 1);
        $this->overrideSetting('maxfiles', 2);
        $this->overrideSetting('file_extensions', 'txt');

        $topic = $this->createTopic();
        $post = Post::query()->where('topic_id', $topic->id)->firstOrFail();
        $post->update(['created_at' => now()]);
        File::factory()->create(['relate_type' => Post::$morphName, 'relate_id' => $post->id, 'user_id' => $this->user->id]);

        // Раньше склейка считала только загруженные заранее файлы, и пост превышал лимит вложений
        $this->actingAs($this->user)
            ->post('/topics/' . $topic->id . '/create', [
                'text'  => '<p>Ответ с файлами</p>',
                'files' => [
                    UploadedFile::fake()->createWithContent('one.txt', 'text'),
                    UploadedFile::fake()->createWithContent('two.txt', 'text'),
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Post::query()->where('topic_id', $topic->id)->count());
        $this->assertSame(1, $post->files()->count());
    }

    public function testTopicWithoutFirstPostIsEdited(): void
    {
        $topic = $this->createTopic();
        Post::query()->where('topic_id', $topic->id)->delete();

        // Раньше текст был обязателен, хотя без первого сообщения поля нет в форме
        $this->actingAs($this->user)
            ->post('/topics/' . $topic->id . '/edit', ['title' => 'Исправленная тема'])
            ->assertRedirect(route('topics.topic', ['id' => $topic->id]));

        $this->assertSame('Исправленная тема', $topic->fresh()->title);
    }

    public function testAnswersOfVotedPollAreLocked(): void
    {
        $topic = $this->createTopic();
        $vote = Vote::factory()->create(['title' => 'Старый вопрос', 'topic_id' => $topic->id, 'count' => 1]);

        $this->actingAs($this->user)
            ->post('/topics/' . $topic->id . '/edit', [
                'title'    => 'Исправленная тема',
                'text'     => 'Исправленное сообщение',
                'question' => 'Новый вопрос опроса',
                'answers'  => ['Да', 'Нет'],
            ])
            ->assertSessionHasErrors(['question' => __('forum::forums.vote_answer_changed_impossible')]);

        $this->assertSame('Старый вопрос', $vote->fresh()->title);
    }

    public function testOldPostIsNotEdited(): void
    {
        $topic = $this->createTopic();
        $post = Post::query()->where('topic_id', $topic->id)->firstOrFail();
        $post->update(['created_at' => now()->subHour()]);

        $this->actingAs($this->user)
            ->post('/posts/' . $post->id . '/edit', ['text' => 'Правка старого сообщения'])
            ->assertSee(__('main.editing_impossible'));

        $this->assertSame('Первое сообщение темы', $post->fresh()->text);
    }

    public function testCuratorEditsPostsOnlyInOpenTopic(): void
    {
        $curator = User::factory()->create();
        $topic = $this->createTopic();
        $post = Post::query()->where('topic_id', $topic->id)->firstOrFail();
        $editUrl = route('posts.edit', ['id' => $post->id, 'page' => 1]);

        // Второе сообщение: ленивую загрузку темы у сообщений ловит только список из нескольких
        Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $this->user->id, 'text' => 'Второе сообщение темы', 'ip' => '127.0.0.1', 'brow' => 'test']);
        $topic->update(['moderators' => $curator->login]);

        $this->actingAs($curator)
            ->get('/topics/' . $topic->id)
            ->assertOk()
            ->assertSee($editUrl, false)
            ->assertSee('name="del[]"', false);

        $topic->update(['closed' => 1, 'close_user_id' => $this->user->id]);

        // Раньше ссылка на правку оставалась, а сама правка отвечала 403
        $this->actingAs($curator)
            ->get('/topics/' . $topic->id)
            ->assertOk()
            ->assertDontSee($editUrl, false)
            ->assertDontSee('name="del[]"', false);

        $this->actingAs($curator)->get($editUrl)->assertForbidden();
    }

    public function testDeniedWithoutMessageKeepsPosts(): void
    {
        // Отказ без текста, например из Gate::before, раньше пропускал удаление
        $curator = User::factory()->create();
        $topic = $this->createTopic();
        $topic->update(['moderators' => $curator->login]);
        $post = Post::query()->where('topic_id', $topic->id)->firstOrFail();

        Gate::before(static fn (User $user, string $ability): ?bool => $ability === 'moderate' ? false : null);

        $this->actingAs($curator)
            ->post('/topics/' . $topic->id . '/delete', ['del' => [$post->id]])
            ->assertSessionHasErrors();

        $this->assertNotNull($post->fresh());
    }

    public function testAuthorClosesAndOpensTopic(): void
    {
        $topic = $this->createTopic();

        $this->actingAs(User::factory()->create())
            ->post('/topics/' . $topic->id . '/close')
            ->assertSessionHasErrors();

        $this->actingAs($this->user)->post('/topics/' . $topic->id . '/close')->assertSessionHas('success');
        $this->assertTrue((bool) $topic->fresh()->closed);

        $this->actingAs($this->user)->post('/topics/' . $topic->id . '/open')->assertSessionHas('success');
        $this->assertFalse((bool) $topic->fresh()->closed);
    }

    public function testBossManagesForums(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs(User::factory()->create(['level' => User::ADMIN]))
            ->post('/admin/forums/create', ['title' => 'Новости'])
            ->assertForbidden();

        $this->actingAs($boss)
            ->post('/admin/forums/create', ['title' => 'Ок'])
            ->assertSessionHasErrors('title');

        $this->actingAs($boss)
            ->post('/admin/forums/' . $this->forum->id . '/edit', ['title' => 'Общий форум', 'parent' => $this->forum->id])
            ->assertSessionHasErrors('parent');

        $this->actingAs($boss)
            ->post('/admin/forums/' . $this->forum->id . '/edit', ['title' => 'Общение', 'description' => '', 'parent' => 0, 'closed' => 1])
            ->assertRedirect(route('admin.forums.index'));

        $this->assertSame('Общение', $this->forum->fresh()->title);
        $this->assertTrue((bool) $this->forum->fresh()->closed);

        $this->createTopic();

        $this->actingAs($boss)
            ->delete('/admin/forums/' . $this->forum->id . '/delete')
            ->assertSessionHasErrors();

        $this->assertNotNull($this->forum->fresh());
    }

    public function testAdminEditsTopicCurators(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);
        $curator = User::factory()->create();
        $topic = $this->createTopic();

        $this->actingAs($admin)
            ->post('/admin/topics/' . $topic->id . '/edit', ['title' => 'Тема с кураторами', 'moderators' => [$curator->login, 'nobody_here']])
            ->assertSessionHasErrors('moderators');

        // Раньше строка вместо списка роняла проверку с 500
        $this->actingAs($admin)
            ->post('/admin/topics/' . $topic->id . '/edit', ['title' => 'Тема с кураторами', 'moderators' => 'nobody_here'])
            ->assertSessionHasErrors('moderators');

        $this->actingAs($admin)
            ->post('/admin/topics/' . $topic->id . '/edit', ['title' => 'Тема с кураторами', 'moderators' => [$curator->login], 'locked' => 1])
            ->assertRedirect(route('admin.forums.forum', ['id' => $this->forum->id]));

        $topic->refresh();
        $this->assertSame($curator->login, $topic->moderators);
        $this->assertTrue((bool) $topic->locked);
    }

    public function testAdminMovesTopic(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);
        $topic = $this->createTopic();
        $other = Forum::factory()->create(['title' => 'Другой форум']);

        // Ошибка раньше уходила под ключ forum, а поле называлось fid
        $this->actingAs($admin)
            ->post('/admin/topics/' . $topic->id . '/move', ['forum_id' => $this->forum->id])
            ->assertSessionHasErrors('forum_id');

        $this->actingAs($admin)
            ->post('/admin/topics/' . $topic->id . '/move', ['forum_id' => $other->id])
            ->assertRedirect(route('admin.forums.forum', ['id' => $other->id]));

        $this->assertSame($other->id, $topic->fresh()->forum_id);
    }

    public function testAdminDeletesOnlyPostsOfTopic(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);
        $topic = $this->createTopic();
        $foreign = $this->createTopic();
        $foreignPost = Post::query()->where('topic_id', $foreign->id)->firstOrFail();
        $post = Post::query()->where('topic_id', $topic->id)->firstOrFail();

        // Раньше удалялось любое сообщение по id, а счётчики пересчитывались только у этой темы
        $this->actingAs($admin)
            ->post('/admin/posts/delete?tid=' . $topic->id, ['del' => [$post->id, $foreignPost->id]])
            ->assertSessionHas('success');

        $this->assertNull($post->fresh());
        $this->assertNotNull($foreignPost->fresh());
    }

    public function testApiErrorsAreShownUnderTheirField(): void
    {
        $headers = ['Authorization' => 'Bearer ' . $this->user->apikey];
        $topic = $this->createTopic();

        $this->forum->update(['closed' => 1]);

        $this->postJson('/api/forums/' . $this->forum->id, ['title' => 'Новая тема форума', 'text' => 'Текст новой темы'], $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors('forum_id');

        $this->postJson('/api/topics/' . $topic->id, ['text' => '<p><b>Ок</b></p>'], $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors('text');
    }

    private function topicPayload(): array
    {
        return [
            'forum_id' => $this->forum->id,
            'title'    => 'Новая тема форума',
            'text'     => '<p>Текст новой темы</p>',
        ];
    }

    private function createTopic(): Topic
    {
        $topic = Topic::factory()->create([
            'forum_id'    => $this->forum->id,
            'title'       => 'Старая тема',
            'user_id'     => $this->user->id,
            'count_posts' => 0,
            'created_at'  => now(),
        ]);

        $post = Post::factory()->create([
            'topic_id' => $topic->id,
            'user_id'  => $this->user->id,
            'text'     => 'Первое сообщение темы',
            'ip'       => '127.0.0.1',
            'brow'     => 'test',
        ]);

        $topic->update(['count_posts' => 1, 'last_post_id' => $post->id]);

        return $topic->fresh();
    }

    public function testAdminTopicEscapesCloserName(): void
    {
        $closer = User::factory()->create(['name' => '<script src=//a.bc>']);
        $topic = Topic::factory()->create(['closed' => 1, 'close_user_id' => $closer->id]);

        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->get('/admin/topics/' . $topic->id)
            ->assertOk()
            ->assertDontSee('<script src=//a.bc>', false)
            ->assertSee(e('<script src=//a.bc>'), false);
    }
}
