<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesApiComments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Modules\Blog\Http\Requests\ArticleRequest;
use Modules\Blog\Http\Requests\ArticleStoreRequest;
use Modules\Blog\Http\Resources\ArticleResource;
use Modules\Blog\Http\Resources\BlogResource;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;
use Modules\Blog\Models\Tag;

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
     * Теги: облако из 100 самых частых, с ?query= — подсказки по началу слова
     */
    public function tags(Request $request): JsonResponse
    {
        $query = $request->string('query')->trim()->value();

        $tags = $query === ''
            ? Tag::cloud()
            : (Str::length($query) < 2 ? [] : Tag::suggest($query)->pluck('articles_count', 'name')->all());

        return response()->json([
            // Ключ — имя тега: числовое имя PHP превращает в int-ключ, отдаём строкой
            'data' => collect($tags)->map(static fn (int $count, int|string $name) => ['name' => (string) $name, 'count' => $count])->values(),
        ]);
    }

    /**
     * Список статей, при указании category_id — статьи категории
     */
    public function index(Request $request): JsonResource
    {
        $categoryId = $request->integer('category_id');
        $tag = $request->string('tag')->trim()->value();
        $user = $this->apiUser($request);

        // Сортировка та же, что на сайте: date, name, visits, rating, comments
        [, $orderBy] = Article::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $articles = Article::query()
            ->active()
            ->when($categoryId, static fn ($query) => $query->where('category_id', $categoryId))
            ->when($user, static fn ($query) => $query->where('articles.user_id', $user->id))
            ->when($tag !== '', static fn ($query) => $query->whereHas('tags', static fn ($query) => $query->where('name', $tag)))
            ->withUserVote()
            ->orderBy(...$orderBy)
            ->with('user', 'category.parent', 'tags', 'files')
            ->paginate($this->apiPerPage($request));

        return ArticleResource::collection($articles);
    }

    /**
     * Статья с комментариями
     */
    public function view(string $id, Request $request): JsonResource
    {
        // Ссылка статьи содержит id в начале слага, принимаем и слаг, и голый id
        $articleId = int(Str::before($id, '-'));

        $article = Article::query()
            ->withUserVote()
            ->where('articles.id', $articleId)
            ->with('user', 'category.parent', 'tags', 'files')
            ->first();

        if (! $article) {
            abort(404, __('blog::blogs.article_not_exist'));
        }

        // Запись — в data, её комментарии страницей — в comments
        return ArticleResource::make($article)
            ->additional(['comments' => $this->apiCommentsBlock($article, $request)]);
    }

    /**
     * Создание статьи
     */
    public function store(ArticleStoreRequest $request, Flood $flood, FileService $files): JsonResponse
    {
        $title = $request->string('title')->value();
        $isDraft = $request->boolean('draft');
        $publishedAt = $request->publishedAt();
        // Статьи проходят модерацию, если она включена и автор не из администрации
        $isModeration = setting('article_moderation') && ! isAdmin();

        $article = Article::query()->create([
            'category_id'  => $request->category()->id,
            'user_id'      => getUser('id'),
            'title'        => $title,
            'slug'         => $title,
            'text'         => $request->string('text')->value(),
            'draft'        => $isDraft,
            'active'       => ! $isDraft && ! $publishedAt && ! $isModeration,
            'published_at' => $publishedAt,
        ]);

        $article->syncTags($request->tagNames());

        // Файлы можно приложить к запросу или загрузить заранее — с relate_id = 0
        $files->attach($article, $request->file('files', []));
        $flood->saveState();

        $article->load('user', 'category', 'tags', 'files');

        return response()->json([
            'message' => $this->createdMessage($isDraft, (bool) $isModeration),
            'article' => ArticleResource::make($article),
        ], 201);
    }

    /**
     * Редактирование своей статьи
     */
    public function update(int $id, ArticleRequest $request): JsonResponse
    {
        $article = Article::query()->find($id);

        if (! $article) {
            abort(404, __('blog::blogs.article_not_exist'));
        }

        if ($article->user_id !== getUser('id')) {
            abort(403, __('main.article_not_author'));
        }

        // publish переводит черновик в публикацию, иначе статья остаётся в прежнем состоянии
        $isPublish = $request->boolean('publish');
        $publishedAt = $request->publishedAt();
        $isDraft = ! $isPublish && $article->draft;
        $isModeration = setting('article_moderation') && ! isAdmin();

        $article->update([
            'category_id' => $request->category()->id,
            'title'       => $request->string('title')->value(),
            'text'        => $request->string('text')->value(),
            'draft'       => $isDraft,
            // При модерации публикация черновика тоже ждёт проверки
            'active'       => ! $isModeration && ($isPublish || (! $isDraft && ! $publishedAt && $article->active)),
            'published_at' => $publishedAt,
        ]);

        $article->syncTags($request->tagNames());
        clearCache('tagCloud');

        $article->load('user', 'category', 'tags', 'files');

        return response()->json([
            'message' => __('blog::blogs.article_success_edited'),
            'article' => ArticleResource::make($article),
        ]);
    }

    /**
     * Сообщение о созданной статье зависит от того, куда она попала
     */
    private function createdMessage(bool $isDraft, bool $isModeration): string
    {
        if ($isDraft) {
            return __('blog::blogs.article_success_drafts');
        }

        return $isModeration
            ? __('blog::blogs.article_moderation_text')
            : __('blog::blogs.article_success_created');
    }
}
