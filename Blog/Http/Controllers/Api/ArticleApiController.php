<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Traits\HandlesApiComments;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Modules\Blog\Http\Resources\ArticleResource;
use Modules\Blog\Http\Resources\BlogResource;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;

class ArticleApiController extends Controller
{
    use HandlesApiComments;

    /**
     * Категории блогов с подкатегориями
     */
    public function categories(): JsonResource
    {
        $categories = Blog::query()
            ->where('parent_id', 0)
            ->orderBy('sort')
            ->with('children')
            ->get();

        return BlogResource::collection($categories);
    }

    /**
     * Список статей, при указании category_id — статьи категории
     */
    public function index(Request $request): JsonResource
    {
        $categoryId = $request->integer('category_id');

        // Сортировка та же, что на сайте: date, name, visits, rating, comments
        [, $orderBy] = Article::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $articles = Article::query()
            ->active()
            ->when($categoryId, static fn ($query) => $query->where('category_id', $categoryId))
            ->select('articles.*', 'polls.vote')
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('articles.id', 'polls.relate_id')
                    ->where('polls.relate_type', Article::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->orderBy(...$orderBy)
            ->with('user', 'category.parent', 'tags', 'files')
            ->paginate($this->apiPerPage($request));

        return ArticleResource::collection($articles);
    }

    /**
     * Статья с комментариями
     */
    public function view(string $slug, Request $request): JsonResource
    {
        // Ссылка статьи содержит id в начале слага, принимаем и то и другое
        $id = int(Str::before($slug, '-'));

        $article = Article::query()
            ->select('articles.*', 'polls.vote')
            ->where('articles.id', $id)
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('articles.id', 'polls.relate_id')
                    ->where('polls.relate_type', Article::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->with('user', 'category.parent', 'tags', 'files')
            ->first();

        if (! $article) {
            abort(404, __('blog::blogs.article_not_exist'));
        }

        // Комментарии страницей, сама статья — в additional
        return CommentResource::collection($this->apiComments($article, $request))
            ->additional(['article' => ArticleResource::make($article)]);
    }
}
