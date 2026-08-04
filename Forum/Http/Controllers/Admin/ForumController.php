<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Controllers\Admin;

use App\Classes\Restatement;
use App\Classes\Validator;
use App\Http\Controllers\Admin\AdminController;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
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
        $forums = Forum::query()
            ->where('parent_id', 0)
            ->with('lastTopic.lastPost.user')
            ->with('children')
            ->orderBy('sort')
            ->get();

        return view('forum::admin/forums/index', compact('forums'));
    }

    /**
     * Создание раздела
     */
    public function create(Request $request, Validator $validator): RedirectResponse
    {
        if (! isAdmin(User::BOSS)) {
            abort(403, __('errors.forbidden'));
        }

        $title = $request->input('title');

        $validator->length($title, setting('forum_category_min'), setting('forum_category_max'), ['title' => __('validator.text')]);

        if ($validator->isValid()) {
            $max = Forum::query()->max('sort') + 1;

            $forum = Forum::query()->create([
                'title' => $title,
                'sort'  => $max,
            ]);

            return redirect()->route('admin.forums.edit', ['id' => $forum->id])
                ->with('success', __('forum::forums.forum_success_created'));
        }

        return redirect()->route('admin.forums.index')
            ->withInput()
            ->withErrors($validator->getErrors());
    }

    /**
     * Редактирование форума
     */
    public function edit(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        if (! isAdmin(User::BOSS)) {
            abort(403, __('errors.forbidden'));
        }

        $forum = Forum::query()->with('children')->find($id);

        if (! $forum) {
            abort(404, __('forum::forums.forum_not_exist'));
        }

        if ($request->isMethod('post')) {
            $parent = int($request->input('parent'));
            $title = $request->input('title');
            $description = $request->input('description');
            $sort = int($request->input('sort'));
            $closed = empty($request->input('closed')) ? 0 : 1;

            $validator
                ->length($title, setting('forum_category_min'), setting('forum_category_max'), ['title' => __('validator.text')])
                ->length($description, setting('forum_description_min'), setting('forum_description_max'), ['description' => __('validator.text')])
                ->notEqual($parent, $forum->id, ['parent' => __('forum::forums.forum_invalid')]);

            if (! empty($parent) && $forum->children->isNotEmpty()) {
                $validator->addError(['parent' => __('forum::forums.forum_has_subforums')]);
            }

            if ($validator->isValid()) {
                $forum->update([
                    'parent_id'   => $parent,
                    'title'       => $title,
                    'description' => $description,
                    'sort'        => $sort,
                    'closed'      => $closed,
                ]);

                return redirect()->route('admin.forums.index')
                    ->with('success', __('forum::forums.forum_success_edited'));
            }

            return redirect()->route('admin.forums.edit', ['id' => $forum->id])
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        $forums = $forum->getChildren();

        return view('forum::admin/forums/edit', compact('forums', 'forum'));
    }

    /**
     * Удаление раздела
     */
    public function delete(int $id, Validator $validator): RedirectResponse
    {
        if (! isAdmin(User::BOSS)) {
            abort(403, __('errors.forbidden'));
        }

        $forum = Forum::query()->with('children')->find($id);

        if (! $forum) {
            abort(404, __('forum::forums.forum_not_exist'));
        }

        $validator->true($forum->children->isEmpty(), __('forum::forums.forum_has_subforums'));

        $topic = Topic::query()->where('forum_id', $forum->id)->first();
        if ($topic) {
            $validator->addError(__('forum::forums.forum_has_topics'));
        }

        if (! $validator->isValid()) {
            return redirect()->route('admin.forums.index')
                ->withErrors($validator->getErrors());
        }

        $forum->delete();

        return redirect()->route('admin.forums.index')
            ->with('success', __('forum::forums.forum_success_deleted'));
    }

    /**
     * Пересчет данных
     */
    public function restatement(): RedirectResponse
    {
        if (! isAdmin(User::BOSS)) {
            abort(403, __('errors.forbidden'));
        }

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
        $forum = Forum::query()->with('parent', 'children.lastTopic.lastPost.user')->find($id);

        if (! $forum) {
            abort(404, __('forum::forums.forum_not_exist'));
        }

        $topics = Topic::query()
            ->select('topics.*', 'bookmarks.count_posts as bookmark_posts')
            ->where('forum_id', $forum->id)
            ->leftJoin('bookmarks', static function (JoinClause $join) {
                $join->on('topics.id', 'bookmarks.topic_id')
                    ->where('bookmarks.user_id', getUser('id'));
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
    public function editTopic(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $topic = Topic::query()->find($id);

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        if ($request->isMethod('post')) {
            $title = $request->input('title');
            $note = $request->input('note');
            $moderators = array_filter((array) $request->input('moderators', []), static fn ($login) => is_string($login) && $login !== '');
            $locked = empty($request->input('locked')) ? 0 : 1;
            $closed = empty($request->input('closed')) ? 0 : 1;
            $closeUserId = $closed ? getUser('id') : null;

            $curators = User::query()->whereIn('login', $moderators)->pluck('login');
            $missing = array_diff($moderators, $curators->all());

            $validator
                ->length($title, setting('forum_title_min'), setting('forum_title_max'), ['title' => __('validator.text')])
                ->length($note, setting('forum_note_min'), setting('forum_note_max'), ['note' => __('validator.text_long')])
                ->empty($missing, ['moderators' => __('validator.user_login', ['login' => implode(', ', $missing)])])
                ->lte(count($moderators), self::MAX_CURATORS, ['moderators' => __('forum::forums.curators_limit', ['limit' => self::MAX_CURATORS])]);

            if ($validator->isValid()) {
                $topic->update([
                    'title'         => $title,
                    'note'          => $note,
                    'moderators'    => $curators->implode(','),
                    'locked'        => $locked,
                    'closed'        => $closed,
                    'close_user_id' => $closeUserId,
                ]);

                clearCache(['statForums', 'recentTopics']);

                return redirect()->route('admin.forums.forum', ['id' => $topic->forum_id])
                    ->with('success', __('forum::forums.topic_success_edited'));
            }

            return redirect()->route('admin.topics.edit', ['id' => $topic->id])
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        return view('forum::admin/forums/edit_topic', compact('topic'));
    }

    /**
     * Перенос темы
     */
    public function moveTopic(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $topic = Topic::query()->find($id);

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        if ($request->isMethod('post')) {
            $fid = int($request->input('fid'));

            $forum = Forum::query()->find($fid);

            $validator->notEmpty($forum, ['forum' => __('forum::forums.forum_not_exist')]);

            if ($forum) {
                $validator->empty($forum->closed, ['forum' => __('forum::forums.forum_closed')]);
                $validator->notEqual($topic->forum_id, $forum->id, ['forum' => __('forum::forums.forum_invalid')]);
            }

            if ($validator->isValid()) {
                $oldTopic = $topic->replicate();

                $topic->update([
                    'forum_id' => $forum->id,
                ]);

                // Обновление счетчиков
                $topic->forum->restatement();
                $oldTopic->forum->restatement();

                return redirect()->route('admin.forums.forum', ['id' => $topic->forum_id])
                    ->with('success', __('forum::forums.topic_success_moved'));
            }

            return redirect()->route('admin.topics.move', ['id' => $topic->id])
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        $forums = $topic->forum->getChildren();

        return view('forum::admin/forums/move_topic', compact('forums', 'topic'));
    }

    /**
     * Закрытие и закрепление тем
     */
    public function actionTopic(int $id, Request $request): RedirectResponse
    {
        $page = int($request->input('page', 1));

        $topic = Topic::query()->find($id);

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        $redirect = redirect()->route('admin.topics.topic', ['id' => $topic->id, 'page' => $page]);

        switch ($request->input('type')) {
            case 'closed':
                $topic->update([
                    'closed'        => 1,
                    'close_user_id' => getUser('id'),
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
        $page = int($request->input('page', 1));

        $topic = Topic::query()->find($id);

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

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
        $topic = Topic::query()->where('id', $id)->with('forum.parent')->first();

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

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
            $vote->load('poll');

            if ($vote->answers->isNotEmpty()) {
                $results = Arr::pluck($vote->answers, 'result', 'answer');
                $max = max($results);

                arsort($results);

                $vote->voted = $results;

                $vote->sum = ($vote->count > 0) ? $vote->count : 1;
                $vote->max = ($max > 0) ? $max : 1;
            }
        }

        $files = File::query()
            ->where('relate_type', Post::$morphName)
            ->where('relate_id', 0)
            ->where('user_id', getUser('id'))
            ->orderBy('created_at')
            ->get();

        return view('forum::admin/forums/topic', compact('topic', 'posts', 'vote', 'files'));
    }

    /**
     * Редактирование сообщения
     */
    public function editPost(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $page = int($request->input('page', 1));

        $post = Post::query()->find($id);

        if (! $post) {
            abort(404, __('forum::forums.post_not_exist'));
        }

        if ($request->isMethod('post')) {
            $msg = $request->input('msg');

            $validator->length($msg, setting('forum_text_min'), setting('forum_text_max'), ['msg' => __('validator.text')]);

            if ($validator->isValid()) {
                $msg = antimat($msg);

                $post->update([
                    'text'         => $msg,
                    'edit_user_id' => getUser('id'),
                ]);

                return redirect()->route('admin.topics.topic', ['id' => $post->topic_id, 'page' => $page])
                    ->with('success', __('main.message_edited_success'));
            }

            return redirect()->route('admin.posts.edit', ['id' => $post->id, 'page' => $page])
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        return view('forum::admin/forums/edit_post', compact('post', 'page'));
    }

    /**
     * Удаление сообщений
     */
    public function deletePosts(Request $request, Validator $validator): RedirectResponse
    {
        $tid = int($request->input('tid'));
        $page = int($request->input('page', 1));
        $del = intar($request->input('del'));

        $topic = Topic::query()->where('id', $tid)->first();

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        $validator->true($del, __('validator.deletion'));

        if (! $validator->isValid()) {
            return redirect()->route('admin.topics.topic', ['id' => $topic->id, 'page' => $page])
                ->withErrors($validator->getErrors());
        }

        $posts = Post::query()
            ->whereIn('id', $del)
            ->get();

        $posts->each(static function (Post $post) {
            $post->delete();
        });

        // Обновление счетчиков
        $topic->restatement();

        return redirect()->route('admin.topics.topic', ['id' => $topic->id, 'page' => $page])
            ->with('success', __('main.messages_deleted_success'));
    }
}
