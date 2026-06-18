<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Load;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Load\Models\Down;

class ActiveController extends Controller
{
    /**
     * Текущий пользователь
     */
    public ?User $user;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $login = $request->input('user', getUser('login'));
            $this->user = getUserByLogin($login);

            if (! $this->user) {
                abort(404, __('validator.user'));
            }

            return $next($request);
        });
    }

    /**
     * Мои файлы
     */
    public function files(Request $request): View
    {
        $active = (bool) $request->input('active', true);
        $user = $this->user;

        if (getUser() && getUser('id') !== $user->id) {
            $active = true;
        }

        $downs = Down::query()
            ->select('downs.*', 'polls.vote')
            ->active($active)
            ->where('downs.user_id', $user->id)
            ->leftJoin('polls', function ($join) {
                $join->on('downs.id', 'polls.relate_id')
                    ->where('polls.relate_type', Down::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->orderByDesc('downs.created_at')
            ->with('category', 'user')
            ->paginate(setting('downlist'))
            ->appends([
                'user'   => $user->login,
                'active' => $active,
            ]);

        $activeCount = Down::query()
            ->active(true)
            ->where('user_id', $user->id)
            ->count();

        $pendingCount = Down::query()
            ->active(false)
            ->where('user_id', $user->id)
            ->count();

        return view('load::downs/active_files', compact('downs', 'user', 'active', 'activeCount', 'pendingCount'));
    }

    /**
     * Мои комментарии
     */
    public function comments(): View
    {
        $user = $this->user;

        $comments = Comment::query()
            ->select('comments.*', 'title', 'count_comments', 'polls.vote')
            ->where('comments.relate_type', Down::$morphName)
            ->where('comments.user_id', $user->id)
            ->leftJoin('downs', 'comments.relate_id', 'downs.id')
            ->leftJoin('polls', function ($join) {
                $join->on('comments.id', 'polls.relate_id')
                    ->where('polls.relate_type', Comment::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->orderByDesc('comments.created_at')
            ->with('user')
            ->paginate(setting('comments_per_page'))
            ->appends(['user' => $user->login]);

        return view('load::downs/active_comments', compact('comments', 'user'));
    }
}
