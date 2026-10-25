<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\DeleteSelectedRequest;
use App\Models\File;
use App\Models\User;
use App\Support\CategoryTree;
use App\Support\Restatement;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forum\Http\Requests\ForumCategoryRequest;
use Modules\Forum\Http\Requests\PostEditRequest;
use Modules\Forum\Http\Requests\TopicAdminRequest;
use Modules\Forum\Http\Requests\TopicMoveRequest;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Models\Vote;

class ForumController extends AdminController
{
    /**
     * Максимальное количество кураторов темы
     */
    public const int MAX_CURATORS = 10;

    /**
     * Главная страница
     */
    public function index(): View
    {
        // Дерево строит компонент: он же собирает поле порядка и обязан видеть
        // тот же список, поэтому раскладывать его здесь незачем
        $forums = Forum::query()
            ->orderBy('sort')
            ->with('lastTopic.lastPost.user')
            ->get();

        // Счётчик раздела показывает всю ветку: вложенные темы и сообщения
        // принадлежат ему не меньше собственных
        CategoryTree::totals($forums, [
            'count_topics' => 'total_topics',
            'count_posts'  => 'total_posts',
        ]);

        return view('forum::admin/forums/index', compact('forums'));
    }

    /**
     * Создание раздела
     */
    public function create(ForumCategoryRequest $request): RedirectResponse
    {
        $forum = Forum::query()->create([
            'title' => $request->string('title')->value(),
            'sort'  => Forum::query()->max('sort') + 1,
        ]);

        return redirect()->route('admin.forums.edit', ['id' => $forum->id])
            ->with('success', __('forum::forums.forum_success_created'));
    }

    /**
     * Редактирование форума
     */
    public function edit(int $id): View
    {
        $forum = $this->findForum($id);
        $forums = $forum->getChildren();

        return view('forum::admin/forums/edit', compact('forums', 'forum'));
    }

    /**
     * Сохранение форума
     */
    public function update(int $id, ForumCategoryRequest $request): RedirectResponse
    {
        $this->findForum($id)->update([
            'parent_id'   => $request->integer('parent'),
            'title'       => $request->string('title')->value(),
            'description' => $request->string('description')->value(),
            'closed'      => $request->boolean('closed'),
        ]);

        return redirect()->route('admin.forums.index')
            ->with('success', __('forum::forums.forum_success_edited'));
    }

    /**
     * Удаление раздела
     */
    public function delete(int $id): RedirectResponse
    {
        $forum = $this->findForum($id);
        $redirect = redirect()->route('admin.forums.index');

        if ($forum->children->isNotEmpty()) {
            return $redirect->withErrors(__('forum::forums.forum_has_subforums'));
        }

        if (Topic::query()->where('forum_id', $forum->id)->exists()) {
            return $redirect->withErrors(__('forum::forums.forum_has_topics'));
        }

        $forum->delete();

        return $redirect->with('success', __('forum::forums.forum_success_deleted'));
    }

    /**
     * Сохраняет порядок и вложенность разделов
     */
    public function sort(Request $request): RedirectResponse
    {
        $order = $request->input('order');

        CategoryTree::reorder(Forum::class, is_string($order) ? $order : '');

        return redirect()
            ->route('admin.forums.index')
            ->with('success', __('forum::forums.forums_success_sorted'));
    }

    /**
     * Пересчет данных
     */
    public function restatement(): RedirectResponse
    {
        Restatement::run(['forums', 'votes']);

        return redirect()
            ->route('admin.forums.index')
            ->with('success', __('main.success_recounted'));
    }

    /**
     * Просмотр тем раздела
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

        $topics = Topic::query()
            ->select('topics.*', 'bookmarks.count_posts as bookmark_posts')
            ->where('forum_id', $forum->id)
            ->leftJoin('bookmarks', static function (JoinClause $join) {
                $join->on('topics.id', 'bookmarks.topic_id')
                    ->where('bookmarks.user_id', auth()->id());
            })
            ->orderByDesc('locked')
            ->orderByDesc('updated_at')
            ->with('lastPost.user')
            ->paginate(setting('forumtem'));

        return view('forum::admin/forums/forum', compact('forum', 'topics'));
    }

    /**
     * Редактирование темы
     */
    public function editTopic(int $id): View
    {
        $topic = $this->findTopic($id);

        return view('forum::admin/forums/edit_topic', compact('topic'));
    }

    /**
     * Сохранение темы
     */
    public function updateTopic(int $id, TopicAdminRequest $request): RedirectResponse
    {
        $topic = $this->findTopic($id);
        $closed = $request->boolean('closed');

        $topic->update([
            'title'         => $request->string('title')->value(),
            'note'          => $request->string('note')->value(),
            'moderators'    => $request->curators(),
            'locked'        => $request->boolean('locked'),
            'closed'        => $closed,
            'close_user_id' => $closed ? auth()->id() : null,
        ]);

        clearCache(['statForums', 'recentTopics']);

        return redirect()->route('admin.forums.forum', ['id' => $topic->forum_id])
            ->with('success', __('forum::forums.topic_success_edited'));
    }

    /**
     * Перенос темы
     */
    public function moveTopic(int $id): View
    {
        $topic = $this->findTopic($id);
        $forums = $topic->forum->getChildren();

        return view('forum::admin/forums/move_topic', compact('forums', 'topic'));
    }

    /**
     * Сохранение переноса
     */
    public function storeMove(int $id, TopicMoveRequest $request): RedirectResponse
    {
        $topic = $this->findTopic($id);
        $oldForum = $topic->forum;

        $topic->update(['forum_id' => $request->forum()->id]);

        // Обновление счетчиков
        $request->forum()->restatement();
        $oldForum->restatement();

        return redirect()->route('admin.forums.forum', ['id' => $topic->forum_id])
            ->with('success', __('forum::forums.topic_success_moved'));
    }

    /**
     * Закрытие и закрепление тем
     */
    public function actionTopic(int $id, Request $request): RedirectResponse
    {
        $page = $request->integer('page', 1);

        $topic = Topic::query()->findOrFail($id);

        $redirect = redirect()->route('admin.topics.topic', ['id' => $topic->id, 'page' => $page]);

        switch ($request->input('type')) {
            case 'closed':
                $topic->update([
                    'closed'        => 1,
                    'close_user_id' => auth()->id(),
                ]);

                return $redirect->with('success', __('forum::forums.topic_success_closed'));

            case 'open':
                $topic->update([
                    'closed'        => 0,
                    'close_user_id' => null,
                ]);

                return $redirect->with('success', __('forum::forums.topic_success_opened'));

            case 'locked':
                $topic->update(['locked' => 1]);

                return $redirect->with('success', __('forum::forums.topic_success_pinned'));

            case 'unlocked':
                $topic->update(['locked' => 0]);

                return $redirect->with('success', __('forum::forums.topic_success_unpinned'));

            default:
                return $redirect->with('danger', __('main.action_not_selected'));
        }
    }

    /**
     * Удаление тем
     */
    public function deleteTopic(int $id, Request $request): RedirectResponse
    {
        $page = $request->integer('page', 1);

        $topic = Topic::query()->findOrFail($id);

        $topic->delete();
        $topic->forum->restatement();

        clearCache(['statForums', 'recentTopics']);

        return redirect()->route('admin.forums.forum', ['id' => $topic->forum->id, 'page' => $page])
            ->with('success', __('forum::forums.topic_success_deleted'));
    }

    /**
     * Просмотр темы
     */
    public function topic(int $id): View
    {
        $topic = Topic::query()->where('id', $id)->with('forum.parent')->firstOrFail();

        $posts = Post::query()
            ->where('topic_id', $topic->id)
            ->with('files', 'user', 'editUser', 'poll')
            ->orderBy('created_at')
            ->paginate(setting('forumpost'));

        // Кураторы
        if ($topic->moderators) {
            $topic->curators = User::query()->whereIn('login', explode(',', (string) $topic->moderators))->get();
        }

        // Голосование
        $vote = Vote::query()->where('topic_id', $topic->id)->first();

        if ($vote) {
            $vote->load('answers');
        }

        $files = File::query()
            ->pending(Post::$morphName, auth()->id())
            ->ordered()
            ->get();

        return view('forum::admin/forums/topic', compact('topic', 'posts', 'vote', 'files'));
    }

    /**
     * Редактирование сообщения
     */
    public function editPost(int $id, Request $request): View
    {
        $page = $request->integer('page', 1);
        $post = $this->findPost($id);

        return view('forum::admin/forums/edit_post', compact('post', 'page'));
    }

    /**
     * Сохранение сообщения
     */
    public function updatePost(int $id, PostEditRequest $request): RedirectResponse
    {
        $post = $this->findPost($id);

        $post->update([
            'text'         => $request->string('text')->value(),
            'edit_user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.topics.topic', ['id' => $post->topic_id, 'page' => $request->integer('page', 1)])
            ->with('success', __('main.message_edited_success'));
    }

    /**
     * Удаление сообщений
     */
    public function deletePosts(DeleteSelectedRequest $request): RedirectResponse
    {
        $topic = $this->findTopic($request->integer('tid'));

        // Только сообщения этой темы: счётчики пересчитываются у неё одной
        Post::query()
            ->where('topic_id', $topic->id)
            ->whereIn('id', $request->ids())
            ->get()
            ->each->delete();

        $topic->restatement();

        return redirect()->route('admin.topics.topic', ['id' => $topic->id, 'page' => $request->integer('page', 1)])
            ->with('success', __('main.messages_deleted_success'));
    }

    /**
     * Раздел по id
     */
    private function findForum(int $id): Forum
    {
        $forum = Forum::query()->with('children')->findOrFail($id);

        return $forum;
    }

    /**
     * Тема по id
     */
    private function findTopic(int $id): Topic
    {
        $topic = Topic::query()->findOrFail($id);

        return $topic;
    }

    /**
     * Сообщение по id
     */
    private function findPost(int $id): Post
    {
        $post = Post::query()->findOrFail($id);

        return $post;
    }
}
