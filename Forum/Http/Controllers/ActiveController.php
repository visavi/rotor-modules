<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;

class ActiveController extends Controller
{
    /**
     * Вывод тем
     */
    public function topics(Request $request): View
    {
        $user = $this->owner($request);

        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Topic::getSorting($sort, $order);

        $topics = Topic::query()
            ->where('user_id', $user->id)
            ->orderBy(...$orderBy)
            ->with('forum', 'user', 'lastPost.user')
            // Иконке в списке нужен только факт наличия опроса, сам он не грузится
            ->withExists('vote')
            ->paginate(setting('forumtem'))
            ->appends(['user' => $user->login, 'sort' => $sort, 'order' => $order]);

        return view('forum::forums/active_topics', compact('topics', 'user', 'sorting'));
    }

    /**
     * Вывод сообщений
     */
    public function posts(Request $request): View
    {
        $user = $this->owner($request);

        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Post::getSorting($sort, $order);

        $posts = Post::query()
            ->where('user_id', $user->id)
            ->orderBy(...$orderBy)
            ->with('topic', 'user', 'poll')
            ->paginate(setting('forumpost'))
            ->appends(['user' => $user->login, 'sort' => $sort, 'order' => $order]);

        return view('forum::forums/active_posts', compact('posts', 'user', 'sorting'));
    }

    /**
     * Удаление сообщений
     */
    public function destroy(int $id): JsonResponse
    {
        if (! isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => __('forum::forums.posts_deleted_moderators'),
            ], 403);
        }

        $post = Post::query()
            ->where('id', $id)
            ->with('topic.forum')
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => __('forum::forums.post_not_exist'),
            ], 404);
        }

        $post->delete();
        $post->topic->decrement('count_posts');
        $post->topic->forum->decrement('count_posts');

        return response()->json([
            'success' => true,
            'message' => __('main.record_deleted_success'),
        ]);
    }

    /**
     * Владелец страницы: из ?user= или сам вошедший; массив в адресе — свои страницы
     */
    private function owner(Request $request): User
    {
        $login = $request->input('user');

        return User::findByLoginOrFail(is_string($login) ? $login : auth()->user()?->login);
    }
}
