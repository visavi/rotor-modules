<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\File;
use App\Models\Flood;
use App\Models\Reader;
use App\Services\FileService;
use App\Support\CategoryTree;
use App\Traits\HandlesComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Blog\Http\Requests\ArticleRequest;
use Modules\Blog\Http\Requests\ArticleStoreRequest;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;
use Modules\Blog\Models\Tag;
use Random\Engine\Mt19937;
use Random\Randomizer;

class ArticleController extends Controller
{
    use HandlesComments;

    protected string $commentableModelClass = Article::class;

    /**
     * Возвращает маршрут для просмотра комментируемой модели
     */
    protected function commentableViewRoute(Model $model): array
    {
        return ['articles.view', ['slug' => $model->getAttribute('slug')]];
    }

    /**
     * Главная страница
     */
    public function index(): View
    {
        // Дерево берётся целиком: счётчик раздела складывает всю ветку,
        // а связи детей раздаются из памяти, без запроса на раздел
        $categories = Blog::query()
            ->orderBy('sort')
            ->with('new', 'lastArticle.user')
            ->get();

        CategoryTree::totals($categories, ['count_articles' => 'total_articles']);

        $categories = CategoryTree::nest($categories);

        if ($categories->isEmpty()) {
            abort(200, __('blog::blogs.categories_not_created'));
        }

        return view('blog::articles/index', compact('categories'));
    }

    /**
     * Список статей
     */
    public function blog(int $id, Request $request): View
    {
        // Дерево целиком: счётчик подраздела складывает всю его ветку
        $categories = Blog::query()->orderBy('sort')->with('new')->get();

        CategoryTree::totals($categories, ['count_articles' => 'total_articles']);
        CategoryTree::nest($categories);

        $category = $categories->firstWhere('id', $id);

        if (! $category) {
            abort(404, __('blog::blogs.category_not_exist'));
        }

        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Article::getSorting($sort, $order);

        $articles = Article::query()
            ->active()
            ->where('category_id', $id)
            ->orderBy(...$orderBy)
            ->with('user', 'poll')
            ->paginate(setting('blogpost'))
            ->appends(compact('sort', 'order'));

        return view('blog::articles/blog', compact('articles', 'category', 'sorting'));
    }

    /**
     * Просмотр статьи
     */
    public function view(string $slug): View
    {
        $id = Str::before($slug, '-');

        $article = Article::query()
            ->where('id', $id)
            ->with('category.parent', 'tags', 'poll', 'files')
            ->first();

        if (! $article) {
            abort(404, __('blog::blogs.article_not_exist'));
        }

        Reader::countingStat($article);

        ['comments' => $comments, 'files' => $files] = $this->getCommentsData($article);

        return view('blog::articles/view', compact('article', 'comments', 'files'));
    }

    /**
     * Создание статьи
     */
    public function create(Request $request): View
    {
        $this->checkCreationOpen();

        $categories = (new Blog())->getChildren();

        if ($categories->isEmpty()) {
            abort(404, __('blog::blogs.categories_not_created'));
        }

        $cid = int($request->input('cid'));
        $article = new Article();

        $files = File::query()
            ->pending(Article::$morphName, getUser('id'))
            ->ordered()
            ->get();

        return view('blog::articles/create', compact('article', 'categories', 'cid', 'files'));
    }

    /**
     * Публикация статьи
     */
    public function store(ArticleStoreRequest $request, Flood $flood, FileService $files): RedirectResponse
    {
        $title = $request->string('title')->value();
        $publishedAt = $request->publishedAt();
        $isDraft = $request->boolean('draft');
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

        $files->attach($article, $request->file('files', []));
        $flood->saveState();

        $created = $isModeration ? __('blog::blogs.article_moderation_text') : __('blog::blogs.article_success_created');
        $flash = $isDraft ? __('blog::blogs.article_success_drafts') : $created;

        return redirect()
            ->route('articles.view', ['slug' => $article->slug])
            ->with('success', $flash);
    }

    /**
     * Редактирование статьи
     */
    public function edit(int $id): View
    {
        $article = $this->ownArticle($id);
        $categories = $article->category->getChildren();

        return view('blog::articles/edit', compact('article', 'categories'));
    }

    /**
     * Сохранение статьи
     */
    public function update(int $id, ArticleRequest $request): RedirectResponse
    {
        $article = $this->ownArticle($id);

        $isPublish = $request->boolean('publish');
        $publishedAt = $request->publishedAt();
        $isModeration = setting('article_moderation') && ! isAdmin();
        $wasDraft = $article->draft;
        $isDraft = ! $isPublish && $article->draft;

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

        $created = $isModeration ? __('blog::blogs.article_moderation_text') : __('blog::blogs.article_success_created');
        $flash = ($wasDraft && $isPublish) ? $created : __('blog::blogs.article_success_edited');

        return redirect()
            ->route('articles.view', ['slug' => $article->slug])
            ->with('success', $flash);
    }

    /**
     * Просмотр по категориям
     */
    public function authors(): View
    {
        $articles = Article::query()
            ->active()
            ->selectRaw('user_id, count(*) as cnt, sum(count_comments) as count_comments')
            ->join('users', 'articles.user_id', 'users.id')
            ->with('user')
            ->groupBy('articles.user_id')
            ->orderByDesc('cnt')
            ->paginate(setting('bloggroup'));

        return view('blog::articles/authors', compact('articles'));
    }

    /**
     * RSS всех блогов
     */
    public function rss(): Response
    {
        $articles = Article::query()
            ->active()
            ->orderByDesc('created_at')
            ->with('user')
            ->limit(15)
            ->get();

        if ($articles->isEmpty()) {
            abort(200, __('blog::blogs.article_not_exist'));
        }

        return response()
            ->view('blog::articles/rss', compact('articles'))
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Вывод всех тегов
     */
    public function tags(): View
    {
        $tags = Tag::cloud();

        // В кэше порядок по частоте; облако перемешивается раз в час и одинаково у всех —
        // не скачет при обновлении страницы. Свой генератор не сбивает общий mt_rand
        $names = (new Randomizer(new Mt19937((int) date('YmdH'))))->shuffleArray(array_keys($tags));
        $tags = array_replace(array_flip($names), $tags);

        $max = $tags ? max($tags) : 0;
        $min = $tags ? min($tags) : 0;

        return view('blog::articles/tags', compact('tags', 'max', 'min'));
    }

    /**
     * Поиск по тегам
     */
    public function getTag(string $tag): View|RedirectResponse
    {
        $tag = urldecode($tag);

        if (Str::length($tag) < 2) {
            return redirect()
                ->route('blogs.tags')
                ->with('danger', __('blog::blogs.tag_search_rule'));
        }

        $tagModel = Tag::query()->where('name', $tag)->first();
        if (! $tagModel) {
            return redirect()
                ->route('blogs.tags')
                ->with('danger', __('main.empty_found'));
        }

        $articles = $tagModel->articles()
            ->select('articles.*', 'blogs.name')
            ->join('blogs', 'articles.category_id', 'blogs.id')
            ->where('articles.active', true)
            ->orderByDesc('created_at')
            ->with('user', 'poll')
            ->paginate(setting('blogpost'));

        return view('blog::articles/tags_search', compact('articles', 'tag'));
    }

    /**
     * Search tags
     */
    public function searchTags(Request $request): JsonResponse
    {
        $query = $request->input('query');

        if (Str::length($query) < 2) {
            return response()->json();
        }

        $tags = Tag::suggest($query);

        $formattedTags = $tags->map(function ($tag) {
            return [
                'value' => $tag->name,
                'label' => $tag->name,
            ];
        });

        return response()->json($formattedTags);
    }

    /**
     * Новые статьи
     */
    public function newArticles(Request $request): View
    {
        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Article::getSorting($sort, $order);

        $articles = Article::query()
            ->active()
            ->orderBy(...$orderBy)
            ->with('user', 'category', 'poll')
            ->paginate(setting('blogpost'))
            ->appends(compact('sort', 'order'));

        return view('blog::articles/new_articles', compact('articles', 'sorting'));
    }

    /**
     * Новые комментарии
     */
    public function newComments(): View
    {
        $comments = Comment::query()
            ->select('comments.*', 'title', 'count_comments')
            ->where('relate_type', Article::$morphName)
            ->leftJoin('articles', 'comments.relate_id', 'articles.id')
            ->orderByDesc('comments.created_at')
            ->with('user', 'relate', 'poll')
            ->capped()
            ->paginate(setting('comments_per_page'));

        return view('blog::articles/new_comments', compact('comments'));
    }

    /**
     * Статьи пользователя
     */
    public function userArticles(Request $request): View
    {
        $active = (bool) $request->input('active', true);
        $login = $request->input('user', getUser('login'));
        $user = getUserByLogin($login);

        if (! $user) {
            abort(404, __('validator.user'));
        }

        $articles = Article::query()
            ->active($active)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->with('user', 'poll')
            ->paginate(setting('blogpost'))
            ->appends(['user' => $user->login]);

        $activeCount = Article::query()->active()->where('user_id', $user->id)->count();
        $delayCount = Article::query()->active(false)->where('user_id', $user->id)->count();
        $draftCount = Article::query()->active(false)->where('draft', true)->where('user_id', $user->id)->count();

        return view('blog::articles/active_articles', compact('articles', 'user', 'activeCount', 'delayCount', 'active'));
    }

    /**
     * Комментарии пользователя
     */
    public function userComments(Request $request): View
    {
        $login = $request->input('user', getUser('login'));
        $user = getUserByLogin($login);

        if (! $user) {
            abort(404, __('validator.user'));
        }

        $comments = Comment::query()
            ->select('comments.*', 'title', 'count_comments')
            ->where('relate_type', Article::$morphName)
            ->where('comments.user_id', $user->id)
            ->leftJoin('articles', 'comments.relate_id', 'articles.id')
            ->orderByDesc('comments.created_at')
            ->with('user', 'relate', 'poll')
            ->paginate(setting('comments_per_page'))
            ->appends(['user' => $user->login]);

        return view('blog::articles/active_comments', compact('comments', 'user'));
    }

    /**
     * Список всех блогов (Для вывода на главную страницу)
     */
    public function main(): View
    {
        $articles = Article::query()
            ->active()
            ->orderByDesc('created_at')
            ->with('user', 'category.parent')
            ->paginate(setting('blogpost'));

        return view('blog::articles/main', compact('articles'));
    }

    /**
     * Своя статья по id
     */
    private function ownArticle(int $id): Article
    {
        $article = Article::query()->find($id);

        if (! $article) {
            abort(404, __('blog::blogs.article_not_exist'));
        }

        if ($article->user_id !== getUser('id')) {
            abort(200, __('main.article_not_author'));
        }

        return $article;
    }

    /**
     * Публикация статей может быть закрыта настройкой — кроме администрации
     */
    private function checkCreationOpen(): void
    {
        if (! isAdmin() && ! setting('blog_create')) {
            abort(200, __('blog::blogs.articles_closed'));
        }
    }
}
