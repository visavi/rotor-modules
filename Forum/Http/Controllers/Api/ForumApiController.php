<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Models\User;
use App\Services\FileService;
use App\Traits\HandlesApiPagination;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Forum\Http\Requests\PostRequest;
use Modules\Forum\Http\Requests\TopicRequest;
use Modules\Forum\Http\Resources\ForumResource;
use Modules\Forum\Http\Resources\PostResource;
use Modules\Forum\Http\Resources\TopicResource;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Services\TopicService;

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
     * Новые темы — по последнему сообщению. С ?user= — темы пользователя.
     * Сортировка та же, что на сайте: date, visits, posts
     */
    public function newTopics(Request $request): JsonResource
    {
        $user = $this->apiUser($request);
        [, $orderBy] = Topic::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        // Общая лента — первые 1000, как на сайте; темы автора листаются целиком
        $topics = Topic::query()
            ->when($user, static fn (Builder $query, User $user) => $query->where('user_id', $user->id))
            ->with(self::LIST_RELATIONS)
            ->orderBy(...$orderBy)
            ->when(! $user, static fn (Builder $query) => $query->capped())
            ->paginate($this->apiPerPage($request));

        return TopicResource::collection($topics);
    }

    /**
     * Новые сообщения всех тем. С ?user= — сообщения пользователя.
     * Как на сайте: сортировка date или rating, period — за сколько последних дней
     */
    public function newPosts(Request $request): JsonResource
    {
        $user = $this->apiUser($request);
        $period = $request->integer('period');
        [, $orderBy] = Post::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        // Общая лента — первые 1000, как на сайте; сообщения автора листаются целиком
        $posts = Post::query()
            ->withUserVote()
            ->when($user, static fn (Builder $query, User $user) => $query->where('posts.user_id', $user->id))
            ->when($period > 0, static fn (Builder $query) => $query->where('posts.created_at', '>', now()->subDays($period)))
            ->with('user', 'files', 'topic')
            ->orderBy(...$orderBy)
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
            ->where('bookmarks.user_id', auth()->id())
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
    public function createPost(PostRequest $request, Flood $flood, FileService $files): JsonResponse
    {
        $topic = $request->topic();
        $msg = $request->string('text')->value();

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'user_id'  => auth()->id(),
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
    public function createTopic(TopicRequest $request, Flood $flood, TopicService $service, #[CurrentUser] User $user): JsonResponse
    {
        $topic = $service->create(
            $user,
            $request->forum(),
            $request->string('title')->value(),
            $request->string('text')->value(),
            $request->input('question'),
            $request->answers(),
            $request->file('files', []),
        );

        $flood->saveState();

        $topic->refresh()->load('user', 'lastPost.user');

        return response()->json([
            'message' => __('forum::forums.topic_success_created'),
            'topic'   => TopicResource::make($topic),
        ], 201);
    }
}
