<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesApiPagination;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Forum\Http\Resources\ForumResource;
use Modules\Forum\Http\Resources\PostResource;
use Modules\Forum\Http\Resources\TopicResource;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Models\Vote;
use Modules\Forum\Models\VoteAnswer;

class ForumApiController extends Controller
{
    use HandlesApiPagination;

    /**
     * Связи для списков тем: TopicResource отдаёт автора, последнее сообщение и раздел
     */
    private const array LIST_RELATIONS = ['user', 'lastPost.user', 'forum.parent', 'forum.lastTopic.lastPost.user'];

    /**
     * Список категорий форума
     */
    public function categoryForums(): JsonResource
    {
        $forums = Forum::query()
            ->where('parent_id', 0)
            ->with('children', 'lastTopic.lastPost.user')
            ->orderBy('sort')
            ->get();

        if ($forums->isEmpty()) {
            abort(200, __('forum::forums.empty_forums'));
        }

        return ForumResource::collection($forums);
    }

    /**
     * Список тем форума
     */
    public function forums(int $id, Request $request): JsonResource
    {
        $forum = Forum::query()->find($id);

        if (! $forum) {
            abort(404, __('forum::forums.forum_not_exist'));
        }

        $topics = Topic::query()
            ->where('forum_id', $id)
            ->with('user', 'lastPost.user')
            ->orderByDesc('locked')
            ->orderBy('updated_at', $this->apiOrder($request, 'desc'))
            ->paginate($this->apiPerPage($request));

        return TopicResource::collection($topics)
            ->additional(['forum' => ForumResource::make($forum)]);
    }

    /**
     * Новые темы — по последнему сообщению. С ?user= — темы пользователя
     */
    public function newTopics(Request $request): JsonResource
    {
        $user = $this->apiUser($request);

        // Общая лента — первые 1000, как на сайте; темы автора листаются целиком
        $topics = Topic::query()
            ->when($user, static fn (Builder $query) => $query->where('user_id', $user->id))
            ->with(self::LIST_RELATIONS)
            ->orderBy('updated_at', $this->apiOrder($request, 'desc'))
            ->when(! $user, static fn (Builder $query) => $query->capped())
            ->paginate($this->apiPerPage($request));

        return TopicResource::collection($topics);
    }

    /**
     * Новые сообщения всех тем. С ?user= — сообщения пользователя
     */
    public function newPosts(Request $request): JsonResource
    {
        $user = $this->apiUser($request);

        // Общая лента — первые 1000, как на сайте; сообщения автора листаются целиком
        $posts = Post::query()
            ->withUserVote()
            ->when($user, static fn (Builder $query) => $query->where('posts.user_id', $user->id))
            ->with('user', 'files', 'topic')
            ->orderBy('posts.created_at', $this->apiOrder($request, 'desc'))
            ->when(! $user, static fn (Builder $query) => $query->capped())
            ->paginate($this->apiPerPage($request));

        return PostResource::collection($posts);
    }

    /**
     * Закладки: темы с числом сообщений на момент последнего просмотра
     */
    public function bookmarks(Request $request): JsonResource
    {
        $topics = Topic::query()
            ->select('topics.*', 'bookmarks.count_posts as bookmark_posts')
            ->join('bookmarks', 'topics.id', 'bookmarks.topic_id')
            ->where('bookmarks.user_id', getUser('id'))
            ->with(self::LIST_RELATIONS)
            ->orderBy('topics.updated_at', $this->apiOrder($request, 'desc'))
            ->paginate($this->apiPerPage($request));

        return TopicResource::collection($topics);
    }

    /**
     * Список сообщений темы
     */
    public function topics(int $id, Request $request): JsonResource
    {
        // Раздел нужен клиенту для хлебных крошек, lastTopic — для полей ForumResource
        $topic = Topic::query()
            ->with('forum.parent', 'forum.lastTopic.lastPost.user')
            ->find($id);

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        $posts = Post::query()
            ->withUserVote()
            ->where('posts.topic_id', $id)
            ->with('user', 'files')
            ->orderBy('posts.created_at', $this->apiOrder($request, 'asc'))
            ->paginate($this->apiPerPage($request));

        return PostResource::collection($posts)
            ->additional(['topic' => TopicResource::make($topic)]);
    }

    /**
     * Создание сообщения
     */
    public function createPost(int $id, Request $request, Flood $flood, FileService $files): JsonResponse
    {
        $user = getUser();

        $topic = Topic::query()
            ->where('topics.id', $id)
            ->with('forum.parent')
            ->first();

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        $lastPost = Post::query()->where('topic_id', $topic->id)->orderByDesc('id')->value('text');

        $validated = $request->validate([
            'text' => [
                'required',
                'string',
                'min:' . setting('forum_text_min'),
                'max:' . setting('forum_text_max'),
                function (string $attribute, mixed $value, Closure $fail) use ($topic, $flood, $lastPost) {
                    if ($topic->closed) {
                        $fail(__('forum::forums.topic_closed'));
                    }

                    if ($flood->isFlood()) {
                        $fail(__('validator.flood', ['sec' => $flood->getPeriod()]));
                    }

                    if ($lastPost === $value) {
                        $fail(__('forum::forums.post_repeat'));
                    }
                },
            ],
        ] + FileService::rules(Post::$morphName));

        $msg = $validated['text'];

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'user_id'  => $user->id,
            'text'     => $msg,
            'ip'       => getIp(),
            'brow'     => getBrowser(),
        ]);

        // Через FileService, а не uploadFile: он конвертирует видео и забирает файлы, загруженные заранее
        $files->attach($post, $request->file('files', []));

        $flood->saveState();
        sendNotify($msg, route('topics.topic', ['id' => $topic->id, 'pid' => $post->id], false), $topic->title);

        $post->load('user', 'files');

        return response()->json([
            'message' => __('main.message_added_success'),
            'post'    => PostResource::make($post),
        ], 201);
    }

    /**
     * Создание темы
     */
    public function createTopic(int $id, Request $request, Flood $flood, FileService $files): JsonResponse
    {
        $user = getUser();

        $forum = Forum::query()->find($id);

        if (! $forum) {
            abort(404, __('forum::forums.forum_not_exist'));
        }

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'min:' . setting('forum_title_min'),
                'max:' . setting('forum_title_max'),
                function (string $attribute, mixed $value, Closure $fail) use ($forum, $flood) {
                    if ($forum->closed) {
                        $fail(__('forum::forums.forum_closed'));
                    }

                    if ($flood->isFlood()) {
                        $fail(__('validator.flood', ['sec' => $flood->getPeriod()]));
                    }
                },
            ],
            'text'      => ['required', 'string', 'min:' . setting('forum_text_min'), 'max:' . setting('forum_text_max')],
            'question'  => ['nullable', 'string', 'min:' . setting('vote_title_min'), 'max:' . setting('vote_title_max')],
            'answers'   => ['required_with:question', 'array', 'min:2', 'max:10'],
            'answers.*' => ['string', 'min:' . setting('vote_answer_min'), 'max:' . setting('vote_answer_max')],
        ] + FileService::rules(Post::$morphName));

        $topic = Topic::query()->create([
            'forum_id'   => $forum->id,
            'title'      => $validated['title'],
            'user_id'    => $user->id,
            'updated_at' => now(),
        ]);

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'user_id'  => $user->id,
            'text'     => $validated['text'],
            'ip'       => getIp(),
            'brow'     => getBrowser(),
        ]);

        $files->attach($post, $request->file('files', []));

        $flood->saveState();

        if (! empty($validated['question']) && ! empty($validated['answers'])) {
            $answers = array_unique(array_diff($validated['answers'], ['']));
            $poll = Vote::query()->create([
                'title'    => $validated['question'],
                'topic_id' => $topic->id,
            ]);

            $prepareAnswers = array_map(static fn ($answer) => ['vote_id' => $poll->id, 'answer' => $answer], $answers);
            VoteAnswer::query()->insert($prepareAnswers);
        }

        $topic->refresh()->load('user', 'lastPost.user');

        return response()->json([
            'message' => __('forum::forums.topic_success_created'),
            'topic'   => TopicResource::make($topic),
        ], 201);
    }
}
