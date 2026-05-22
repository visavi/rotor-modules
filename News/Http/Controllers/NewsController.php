<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Traits\CommentableTrait;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\News\Models\News;

class NewsController extends Controller
{
    use CommentableTrait;

    protected string $commentableModelClass = News::class;

    public function index(): View
    {
        $news = News::query()
            ->select('news.*', 'polls.vote')
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('news.id', 'polls.relate_id')
                    ->where('polls.relate_type', News::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->orderByDesc('created_at')
            ->with('user', 'files')
            ->paginate(setting('postnews'));

        return view('news::news/index', compact('news'));
    }

    public function view(int $id): View
    {
        $news = News::query()
            ->select('news.*', 'polls.vote')
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('news.id', 'polls.relate_id')
                    ->where('polls.relate_type', News::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->find($id);

        if (! $news) {
            abort(404, __('news::news.news_not_exist'));
        }

        ['comments' => $comments, 'files' => $files] = $this->getCommentsData($news);

        return view('news::news/view', compact('news', 'comments', 'files'));
    }

    public function rss(): Response
    {
        $newses = News::query()
            ->orderByDesc('created_at')
            ->with('user', 'files')
            ->limit(15)
            ->get();

        if ($newses->isEmpty()) {
            abort(200, __('news::news.empty_news'));
        }

        return response()
            ->view('news::news/rss', compact('newses'))
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    public function allComments(): View
    {
        $comments = Comment::query()
            ->select('comments.*', 'title', 'count_comments')
            ->where('relate_type', News::$morphName)
            ->leftJoin('news', 'comments.relate_id', 'news.id')
            ->orderByDesc('created_at')
            ->with('user')
            ->paginate(setting('comments_per_page'));

        return view('news::news/allcomments', compact('comments'));
    }
}
