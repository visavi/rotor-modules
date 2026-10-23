<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Feed;
use App\Models\File;
use App\Models\Flood;
use App\Models\Poll;
use App\Models\Reader;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Forum\Http\Requests\PostEditRequest;
use Modules\Forum\Http\Requests\PostRequest;
use Modules\Forum\Http\Requests\TopicEditRequest;
use Modules\Forum\Models\Bookmark;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Models\Vote;

class TopicController extends Controller
{
    /**
     * Main page
     */
    public function index(int $id, Request $request): View|RedirectResponse
    {
        // Переход к сообщению: страница считается до загрузки темы — по pid
        // ходят и боты, и тяжёлый запрос с закладками ради редиректа не нужен.
        // Несуществующую тему отдаст 404 страница, куда ведёт редирект
        $pid = int($request->input('pid'));
        if ($pid) {
            $position = Post::query()
                ->where('topic_id', $id)
                ->where('id', '<=', $pid)
                ->count();

            return redirect()->route('topics.topic', ['id' => $id, 'page' => Topic::pageOf($position)])
                ->withFragment('post_' . $pid);
        }

        $user = auth()->user();

        $topic = Topic::query()
            ->where('topics.id', $id)
            ->when($user, static function (Builder $query, User $user) {
                $query->select('topics.*', 'bookmarks.count_posts as bookmark_posts')
                    ->leftJoin('bookmarks', static function (JoinClause $join) use ($user) {
                        $join->on('topics.id', 'bookmarks.topic_id')
                            ->where('bookmarks.user_id', $user->id);
                    });
            })
            ->with('forum.parent')
            ->first();

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        $posts = Post::query()
            ->where('topic_id', $topic->id)
            ->with('files', 'user', 'editUser', 'poll')
            ->orderBy('created_at')
            ->paginate(setting('forumpost'));

        if ($posts->onFirstPage()) {
            $firstPost = $posts->first();
        } else {
            $firstPost = Post::query()->where('topic_id', $topic->id)->orderBy('created_at')->first();
        }

        if ($user && $topic->bookmark_posts && $topic->count_posts > $topic->bookmark_posts) {
            Bookmark::query()
                ->where('topic_id', $topic->id)
                ->where('user_id', $user->id)
                ->update(['count_posts' => $topic->count_posts]);
        }

        // Curators
        if ($topic->moderators) {
            $topic->curators = User::query()->whereIn('login', explode(',', (string) $topic->moderators))->get();
            $topic->isModer = $user ? $topic->curators->where('id', $user->id)->isNotEmpty() : false;
        }

        // Visits
        Reader::countingStat($topic);

        // Votes
        $vote = Vote::query()->where('topic_id', $topic->id)->first();

        if ($vote) {
            $vote->load('answers');
        }

        $files = [];
        $description = $firstPost ? truncateDescription($firstPost->text) : $topic->title;

        if ($user) {
            $files = File::query()
                ->pending(Post::$morphName, $user->id)
                ->ordered()
                ->get();
        }

        return view('forum::forums/topic', compact('topic', 'posts', 'vote', 'description', 'files'));
    }

    /**
     * Message creation
     */
    public function create(PostRequest $request, Flood $flood, FileService $files, #[CurrentUser] User $user): RedirectResponse
    {
        $topic = $request->topic();
        $post = $request->lastPost();
        $msg = $request->string('text')->value();

        // Загруженные заранее и пришедшие с формой: склеенный пост не должен превысить лимит
        $countFiles = File::query()
            ->pending(Post::$morphName, $user->id)
            ->count() + count($request->file('files', []));

        if (
            $post
            && $post->created_at->gt(now()->subMinutes(10))
            && $user->id === $post->user_id
            && setting('forum_merge_posts')
            && $countFiles + $post->files->count() <= setting('maxfiles')
            && (Str::length($msg) + Str::length($post->text) <= setting('forum_text_max'))
        ) {
            $post->update(['text' => $post->text . PHP_EOL . $msg]);

            // Склейка не проходит через PostObserver::created — освежаем ленту вручную
            Feed::query()->updateOrInsert(
                ['relate_type' => 'topics', 'relate_id' => $topic->id],
                ['created_at' => now()]
            );

            cache()->increment('feed_version');
        } else {
            $post = Post::query()->create([
                'topic_id' => $topic->id,
                'user_id'  => $user->id,
                'text'     => $msg,
                'ip'       => getIp(),
                'brow'     => getBrowser(),
            ]);
        }

        $files->attach($post, $request->file('files', []));

        $flood->saveState();
        sendNotify($msg, route('topics.topic', ['id' => $topic->id, 'pid' => $post->id], false), $topic->title);

        // count_posts увеличил обсервер на своём инстансе — освежаем для расчёта страницы
        $topic->refresh();

        return redirect()->route('topics.topic', ['id' => $topic->id, 'page' => Topic::pageOf($topic->count_posts)])
            ->withFragment('post_' . $post->id)
            ->with('success', __('main.message_added_success'));
    }

    /**
     * Delete messages
     */
    public function delete(int $id, Request $request, #[CurrentUser] User $user): RedirectResponse
    {
        $del = intar($request->input('del'));
        $topic = $this->findTopic($id);

        $error = match (true) {
            ! $del                       => __('validator.deletion'),
            (bool) $topic->closed        => __('forum::forums.topic_closed'),
            ! $topic->isModerator($user) => __('forum::forums.posts_deleted_curators'),
            default                      => null,
        };

        $redirect = redirect()->route('topics.topic', ['id' => $topic->id, 'page' => int($request->input('page'))]);

        if ($error) {
            return $redirect->withErrors($error);
        }

        Post::query()
            ->where('topic_id', $topic->id)
            ->whereIn('id', $del)
            ->get()
            ->each->delete();

        return $redirect->with('success', __('main.messages_deleted_success'));
    }

    /**
     * Close topic
     */
    public function close(int $id, #[CurrentUser] User $user): RedirectResponse
    {
        $topic = $this->findTopic($id);

        $error = match (true) {
            $user->point < setting('editforumpoint') => __('forum::forums.topic_edited_points', ['point' => plural(setting('editforumpoint'), setting('scorename'))]),
            $topic->user_id !== $user->id            => __('forum::forums.topic_not_author'),
            (bool) $topic->closed                    => __('forum::forums.topic_closed'),
            default                                  => null,
        };

        $redirect = redirect()->route('topics.topic', ['id' => $topic->id]);

        if ($error) {
            return $redirect->withErrors($error);
        }

        $topic->update([
            'closed'        => 1,
            'close_user_id' => $user->id,
        ]);

        return $redirect->with('success', __('forum::forums.topic_success_closed'));
    }

    /**
     * Open topic
     */
    public function open(int $id, #[CurrentUser] User $user): RedirectResponse
    {
        $topic = $this->findTopic($id);

        $error = match (true) {
            $topic->user_id !== $user->id       => __('forum::forums.topic_not_author'),
            $topic->close_user_id !== $user->id => __('forum::forums.topic_opened_author'),
            ! $topic->closed                    => __('forum::forums.topic_already_open'),
            default                             => null,
        };

        $redirect = redirect()->route('topics.topic', ['id' => $topic->id]);

        if ($error) {
            return $redirect->withErrors($error);
        }

        $topic->update([
            'closed'        => 0,
            'close_user_id' => null,
        ]);

        return $redirect->with('success', __('forum::forums.topic_success_opened'));
    }

    /**
     * Topic editing
     */
    public function edit(int $id): View
    {
        $topic = $this->editableTopic($id);

        $post = Post::query()->where('topic_id', $topic->id)->orderBy('id')->first();
        $vote = Vote::query()->where('topic_id', $topic->id)->first();

        if ($vote) {
            $vote->getAnswers = $vote->answers->pluck('answer', 'id')->all();
        }

        return view('forum::forums/topic_edit', compact('post', 'topic', 'vote'));
    }

    /**
     * Topic saving
     */
    public function update(int $id, TopicEditRequest $request): RedirectResponse
    {
        $topic = $this->editableTopic($id);
        $vote = $request->vote();

        $topic->update(['title' => $request->string('title')->value()]);

        $request->firstPost()?->update([
            'text'         => $request->string('text')->value(),
            'edit_user_id' => auth()->id(),
        ]);

        if ($vote) {
            $vote->update(['title' => $request->string('question')->value()]);

            $answers = $request->validated('answers') ?? [];

            if ($answers) {
                // Ключи формы — id прежних вариантов, у новых свои: сравниваем в PHP
                $removed = array_diff($vote->answers()->pluck('id')->all(), array_keys($answers));

                if ($removed) {
                    $vote->answers()->whereIn('id', $removed)->delete();
                }

                $existingAnswers = $vote->answers()->get()->keyBy('id');
                $countAnswers = $existingAnswers->count();

                foreach ($answers as $answerId => $answer) {
                    $existing = $existingAnswers->get($answerId);

                    if ($existing) {
                        $existing->update(['answer' => $answer]);
                    } elseif ($countAnswers < 10) {
                        $vote->answers()->create(['answer' => $answer]);
                        $countAnswers++;
                    }
                }
            }
        }

        return redirect()->route('topics.topic', ['id' => $topic->id])
            ->with('success', __('forum::forums.topic_success_changed'));
    }

    /**
     * Post editing
     */
    public function editPost(int $id, Request $request, #[CurrentUser] User $user): View
    {
        $page = int($request->input('page'));
        $post = $this->editablePost($id, $user);

        return view('forum::forums/topic_edit_post', compact('post', 'page'));
    }

    /**
     * Post saving
     */
    public function updatePost(int $id, PostEditRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $post = $this->editablePost($id, $user);

        $post->update([
            'text'         => $request->string('text')->value(),
            'edit_user_id' => $user->id,
        ]);

        return redirect()->route('topics.topic', ['id' => $post->topic_id, 'page' => int($request->input('page'))])
            ->withFragment('post_' . $post->id)
            ->with('success', __('main.message_edited_success'));
    }

    /**
     * Voting
     */
    public function vote(int $id, Request $request, #[CurrentUser] User $user): RedirectResponse|JsonResponse
    {
        $topic = Topic::query()->find($id);
        $vote = $topic ? Vote::query()->where('topic_id', $id)->first() : null;
        $page = int($request->input('page'));

        if (! $vote) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => __('forum::forums.vote_not_found')]);
            }

            abort(404, __('forum::forums.vote_not_found'));
        }

        $poll = int($request->input('poll'));
        $answer = $poll ? $vote->answers()->where('id', $poll)->first() : null;

        $error = match (true) {
            ! $poll                 => __('forum::forums.vote_answer_not_chosen'),
            (bool) $topic->closed   => __('forum::forums.topic_closed'),
            $vote->poll()->exists() => __('forum::forums.vote_passed'),
            ! $answer               => __('forum::forums.vote_answer_not_found'),
            default                 => null,
        };

        if ($error || ! $answer) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $error]);
            }

            return redirect()->route('topics.topic', ['id' => $vote->topic_id, 'page' => $page])
                ->withErrors($error);
        }

        $vote->increment('count');
        $answer->increment('result');

        Poll::query()->create([
            'relate_type' => Vote::$morphName,
            'relate_id'   => $vote->id,
            'user_id'     => $user->id,
            'vote'        => $answer->answer,
        ]);

        // Из ленты голосуют без перезагрузки страницы: возвращаем свежие результаты
        if ($request->ajax()) {
            $vote->load('answers');

            return response()->json([
                'success' => true,
                'message' => __('forum::forums.vote_success'),
                'html'    => (string) view('forum::forums/_vote', compact('vote', 'page')),
            ]);
        }

        return redirect()->route('topics.topic', ['id' => $vote->topic_id, 'page' => $page])
            ->with('success', __('forum::forums.vote_success'));
    }

    /**
     * Тема по id
     */
    private function findTopic(int $id): Topic
    {
        $topic = Topic::query()->find($id);

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        return $topic;
    }

    /**
     * Своя открытая тема, если хватает баллов на правку
     */
    private function editableTopic(int $id): Topic
    {
        if (auth()->user()?->point < setting('editforumpoint')) {
            abort(200, __('forum::forums.topic_edited_points', ['point' => plural(setting('editforumpoint'), setting('scorename'))]));
        }

        $topic = $this->findTopic($id);

        if ($topic->user_id !== auth()->id()) {
            abort(200, __('forum::forums.topic_not_author'));
        }

        if ($topic->closed) {
            abort(200, __('forum::forums.topic_closed'));
        }

        return $topic;
    }

    /**
     * Сообщение, которое можно править: своё первые 10 минут, куратору — любое
     */
    private function editablePost(int $id, User $user): Post
    {
        $post = Post::query()->with('topic')->find($id);

        if (! $post) {
            abort(404, __('forum::forums.post_not_exist'));
        }

        if ($post->topic->closed) {
            abort(200, __('forum::forums.topic_closed'));
        }

        $isModer = $post->topic->isModerator($user);

        if (! $isModer && $post->user_id !== $user->id) {
            abort(200, __('forum::forums.posts_edited_curators'));
        }

        if (! $isModer && $post->created_at->lt(now()->subMinutes(10))) {
            abort(200, __('main.editing_impossible'));
        }

        return $post;
    }
}
