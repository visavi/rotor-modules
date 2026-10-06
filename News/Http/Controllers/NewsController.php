<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Traits\HandlesComments;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\News\Models\News;

class NewsController extends Controller
{
    use HandlesComments;

    /**
     * Модель для комментариев
     */
    protected string $commentableModelClass = News::class;

    /**
     * Главная страница
     */
    public function index(): View
    {
        $news = News::query()
            ->withUserVote()
            ->orderByDesc('news.created_at')
            ->with('user', 'files')
            ->paginate(setting('postnews'));

        return view('news::news/index', compact('news'));
    }

    /**
     * Вывод новости
     */
    public function view(int $id): View
    {
        $news = News::query()
            ->withUserVote()
            ->find($id);

        if (! $news) {
            abort(404, __('news::news.news_not_exist'));
        }

        ['comments' => $comments, 'files' => $files] = $this->getCommentsData($news);

        return view('news::news/view', compact('news', 'comments', 'files'));
    }

    /**
     * Rss новостей
     */
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

    /**
     * Все комментарии
     */
    public function allComments(): View
    {
        $comments = Comment::query()
            ->select('comments.*', 'title', 'count_comments')
            ->where('relate_type', News::$morphName)
            ->leftJoin('news', 'comments.relate_id', 'news.id')
            ->orderByDesc('comments.created_at')
            ->with('user')
            ->capped()
            ->paginate(setting('comments_per_page'));

        return view('news::news/allcomments', compact('comments'));
    }
}
