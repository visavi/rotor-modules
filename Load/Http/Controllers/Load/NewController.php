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

        $downs = Down::query()
            ->active()
            ->with('user', 'category', 'poll')
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
            ->select('comments.*', 'title', 'count_comments')
            ->where('comments.relate_type', Down::$morphName)
            ->leftJoin('downs', 'comments.relate_id', 'downs.id')
            ->orderByDesc('comments.created_at')
            ->with('user', 'poll')
            ->paginate(setting('comments_per_page'));

        return view('load::downs/new_comments', compact('comments'));
    }
}
