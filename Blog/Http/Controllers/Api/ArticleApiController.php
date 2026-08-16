<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesApiComments;
use Closure;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
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
    public function view(string $id, Request $request): JsonResource
    {
        // Ссылка статьи содержит id в начале слага, принимаем и слаг, и голый id
        $articleId = int(Str::before($id, '-'));

        $article = Article::query()
            ->select('articles.*', 'polls.vote')
            ->where('articles.id', $articleId)
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

        // Запись — в data, её комментарии страницей — в comments
        return ArticleResource::make($article)
            ->additional(['comments' => $this->apiCommentsBlock($article, $request)]);
    }

    /**
     * Создание статьи
     */
    public function store(Request $request, Flood $flood, FileService $files): JsonResponse
    {
        if (! isAdmin() && ! setting('blog_create')) {
            abort(422, __('blog::blogs.articles_closed'));
        }

        $user = getUser();
        $validated = $this->validateArticle($request, $flood);
        $category = $this->findOpenCategory((int) $validated['category_id']);

        $isDraft = (bool) ($validated['draft'] ?? false);
        $publishedAt = $this->resolvePublishedAt($validated['published_at'] ?? null);
        // Статьи проходят модерацию, если она включена и автор не из администрации
        $isModeration = setting('article_moderation') && ! isAdmin();

        $article = Article::query()->create([
            'category_id'  => $category->id,
            'user_id'      => $user->id,
            'title'        => $validated['title'],
            'slug'         => $validated['title'],
            'text'         => antimat($validated['text']),
            'draft'        => $isDraft,
            'active'       => ! $isDraft && ! $publishedAt && ! $isModeration,
            'published_at' => $publishedAt,
        ]);

        $this->syncTags($article, $validated['tags']);

        // Файлы можно приложить к запросу или загрузить заранее — с relate_id = 0
        $files->attachUploaded($article, $request->file('files', []));
        $files->attachPending($article);
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
    public function update(int $id, Request $request): JsonResponse
    {
        $article = Article::query()->find($id);

        if (! $article) {
            abort(404, __('blog::blogs.article_not_exist'));
        }

        if ($article->user_id !== getUser('id')) {
            abort(403, __('main.article_not_author'));
        }

        $validated = $this->validateArticle($request);
        $category = $this->findOpenCategory((int) $validated['category_id']);

        // publish переводит черновик в публикацию, иначе статья остаётся в прежнем состоянии
        $isPublish = $request->boolean('publish');
        $publishedAt = $isPublish ? null : $this->resolvePublishedAt($validated['published_at'] ?? null);
        $isDraft = ! $isPublish && $article->draft;
        $isModeration = setting('article_moderation') && ! isAdmin();

        $article->update([
            'category_id'  => $category->id,
            'title'        => $validated['title'],
            'text'         => antimat($validated['text']),
            'draft'        => $isDraft,
            'active'       => $isPublish || (! $isDraft && ! $publishedAt && ! $isModeration && $article->active),
            'published_at' => $publishedAt,
        ]);

        $this->syncTags($article, $validated['tags']);
        clearCache('tagCloud');

        $article->load('user', 'category', 'tags', 'files');

        return response()->json([
            'message' => __('blog::blogs.article_success_edited'),
            'article' => ArticleResource::make($article),
        ]);
    }

    /**
     * Общие правила статьи
     */
    private function validateArticle(Request $request, ?Flood $flood = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'min:1'],
            'title'       => ['required', 'string', 'min:' . setting('blog_title_min'), 'max:' . setting('blog_title_max')],
            'text'        => [
                'required',
                'string',
                'min:' . setting('blog_text_min'),
                'max:' . setting('blog_text_max'),
                static function (string $attribute, mixed $value, Closure $fail) use ($flood) {
                    if ($flood && $flood->isFlood()) {
                        $fail(__('validator.flood', ['sec' => $flood->getPeriod()]));
                    }
                },
            ],
            'tags'         => ['required', 'array', 'min:1', 'max:10'],
            'tags.*'       => ['string', 'min:' . setting('blog_tag_min'), 'max:' . setting('blog_tag_max')],
            'draft'        => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date', 'after:now'],
        ] + FileService::rules(Article::$morphName));
    }

    /**
     * Отложенная публикация доступна только администрации
     */
    private function resolvePublishedAt(?string $publishedAt): ?string
    {
        return $publishedAt && isAdmin() ? $publishedAt : null;
    }

    /**
     * Пересобирает теги статьи, порядок сохраняется
     */
    private function syncTags(Article $article, array $tags): void
    {
        $tagIds = [];

        foreach (array_values(array_unique($tags)) as $key => $name) {
            $tag = Tag::query()->firstOrCreate(['name' => Str::lower($name)]);
            $tagIds[$tag->id] = ['sort' => $key];
        }

        $article->tags()->sync($tagIds);
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

    /**
     * Находит открытую категорию блога
     */
    private function findOpenCategory(int $id): Blog
    {
        $category = Blog::query()->find($id);

        if (! $category) {
            abort(422, __('blog::blogs.category_not_exist'));
        }

        if ($category->closed) {
            abort(422, __('blog::blogs.category_closed'));
        }

        return $category;
    }
}
