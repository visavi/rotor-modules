<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Load;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Load\Models\Down;

class NewController extends Controller
{
    /**
     * Новые файлы
     */
    public function files(Request $request): View
    {
        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Down::getSorting($sort, $order);
        $orderBy[0] = 'downs.' . $orderBy[0];

        $downs = Down::query()
            ->select('downs.*', 'polls.vote')
            ->active()
            ->leftJoin('polls', function ($join) {
                $join->on('downs.id', 'polls.relate_id')
                    ->where('polls.relate_type', Down::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->with('user', 'category')
            ->orderBy(...$orderBy)
            ->paginate(setting('downlist'))
            ->appends(compact('sort', 'order'));

        return view('load::downs/new_files', compact('downs', 'sorting'));
    }

    /**
     * Новые комментарии
     */
    public function comments(): View
    {
        $comments = Comment::query()
            ->select('comments.*', 'title', 'count_comments', 'polls.vote')
            ->where('comments.relate_type', Down::$morphName)
            ->leftJoin('downs', 'comments.relate_id', 'downs.id')
            ->leftJoin('polls', function ($join) {
                $join->on('comments.id', 'polls.relate_id')
                    ->where('polls.relate_type', Comment::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->orderByDesc('comments.created_at')
            ->with('user')
            ->paginate(setting('comments_per_page'));

        return view('load::downs/new_comments', compact('comments'));
    }
}
