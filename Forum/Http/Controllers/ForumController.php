<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Flood;
use App\Support\CategoryTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Forum\Http\Requests\TopicRequest;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Services\TopicService;

class ForumController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        // Дерево берётся целиком: счётчик раздела складывает всю ветку,
        // а связи детей раздаются из памяти, без запроса на раздел
        $categories = Forum::query()
            ->with('lastTopic.lastPost.user')
            ->orderBy('sort')
            ->get();

        CategoryTree::totals($categories, [
            'count_topics' => 'total_topics',
            'count_posts'  => 'total_posts',
        ]);

        $forums = CategoryTree::nest($categories);

        if ($forums->isEmpty()) {
            abort(200, __('forum::forums.empty_forums'));
        }

        return view('forum::forums/index', compact('forums'));
    }

    /**
     * Страница списка тем
     */
    public function forum(int $id): View
    {
        // Дерево целиком: счётчик подраздела складывает всю его ветку
        $categories = Forum::query()
            ->orderBy('sort')
            ->with('lastTopic.lastPost.user')
            ->get();

        CategoryTree::totals($categories, [
            'count_topics' => 'total_topics',
            'count_posts'  => 'total_posts',
        ]);

        CategoryTree::nest($categories);

        $forum = $categories->firstWhere('id', $id);

        if (! $forum) {
            abort(404, __('forum::forums.forum_not_exist'));
        }

        $user = getUser();

        $topics = Topic::query()
            ->where('forum_id', $forum->id)
            ->when($user, static function (Builder $query) use ($user) {
                $query->select('topics.*', 'bookmarks.count_posts as bookmark_posts')
                    ->leftJoin('bookmarks', static function (JoinClause $join) use ($user) {
                        $join->on('topics.id', 'bookmarks.topic_id')
                            ->where('bookmarks.user_id', $user->id);
                    });
            })
            ->orderByDesc('locked')
            ->orderByDesc('updated_at')
            ->with('lastPost.user')
            // Иконке в списке нужен только факт наличия опроса, сам он не грузится
            ->withExists('vote')
            ->paginate(setting('forumtem'));

        return view('forum::forums/forum', compact('forum', 'topics'));
    }

    /**
     * Создание новой темы
     */
    public function create(Request $request): View
    {
        $forums = (new Forum())->getChildren();

        if ($forums->isEmpty()) {
            abort(200, __('forum::forums.empty_forums'));
        }

        $fid = int($request->input('fid'));

        $files = File::query()
            ->pending(Post::$morphName, getUser('id'))
            ->ordered()
            ->get();

        return view('forum::forums/topic_create', compact('forums', 'fid', 'files'));
    }

    /**
     * Сохранение темы
     */
    public function store(TopicRequest $request, Flood $flood, TopicService $service): RedirectResponse
    {
        $topic = $service->create(
            getUser(),
            $request->forum(),
            $request->string('title')->value(),
            $request->string('text')->value(),
            $request->input('question'),
            $request->answers(),
            $request->file('files', []),
        );

        $flood->saveState();

        return redirect()->route('topics.topic', ['id' => $topic->id])
            ->with('success', __('forum::forums.topic_success_created'));
    }

    /**
     * RSS всех топиков
     */
    public function rss(): Response
    {
        $topics = Topic::query()
            ->where('closed', 0)
            ->with('lastPost.user')
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get();

        if ($topics->isEmpty()) {
            abort(200, __('forum::forums.topics_not_created'));
        }

        return response()
            ->view('forum::forums/rss', compact('topics'))
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
