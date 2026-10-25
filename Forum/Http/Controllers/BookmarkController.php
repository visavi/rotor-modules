<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forum\Models\Bookmark;
use Modules\Forum\Models\Topic;
use Symfony\Component\HttpFoundation\Response;

class BookmarkController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $topics = Bookmark::query()
            ->select('bookmarks.count_posts as bookmark_posts', 'bookmarks.topic_id', 'topics.*')
            ->where('bookmarks.user_id', auth()->id())
            ->leftJoin('topics', 'bookmarks.topic_id', 'topics.id')
            ->with([
                // Иконке в списке нужен только факт наличия опроса, сам он не грузится
                'topic' => static fn ($query) => $query->withExists('vote'),
                'topic.user',
                'topic.lastPost.user',
            ])
            ->orderByDesc('updated_at')
            ->paginate(setting('forumtem'));

        return view('forum::forums/bookmarks', compact('topics'));
    }

    /**
     * Добавление / удаление закладок
     */
    public function perform(Request $request): Response
    {
        if (! $request->ajax()) {
            return redirect('/');
        }

        $topic = Topic::query()->find($request->integer('tid'));

        if (! $topic) {
            return response()->json([
                'success' => false,
                'message' => __('forum::forums.topic_not_exist'),
            ], 404);
        }

        $bookmark = Bookmark::query()
            ->where('topic_id', $topic->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($bookmark) {
            $bookmark->delete();

            return response()->json([
                'success' => true,
                'type'    => 'deleted',
                'message' => __('forum::forums.bookmark_success_deleted'),
                // Ссылка предлагает обратное действие, текст готовит сервер
                'html' => __('forum::forums.to_bookmarks'),
            ]);
        }

        Bookmark::query()->create([
            'user_id'     => auth()->id(),
            'topic_id'    => $topic->id,
            'count_posts' => $topic->count_posts,
        ]);

        return response()->json([
            'success' => true,
            'type'    => 'added',
            'message' => __('forum::forums.bookmark_success_added'),
            'html'    => __('forum::forums.from_bookmarks'),
        ]);
    }

    /**
     * Удаление закладок
     */
    public function delete(Request $request): RedirectResponse
    {
        $topicIds = intar($request->input('del'));
        $redirect = redirect()->route('forums.bookmarks', ['page' => $request->integer('page')]);

        if (! $topicIds) {
            return $redirect->withErrors(__('forum::forums.bookmarks_missing'));
        }

        Bookmark::query()
            ->whereIn('topic_id', $topicIds)
            ->where('user_id', auth()->id())
            ->delete();

        return $redirect->with('success', __('forum::forums.bookmarks_selected_deleted'));
    }
}
