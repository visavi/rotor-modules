<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Support\CategoryTree;
use App\Support\Restatement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Blog\Http\Requests\ArticleRequest;
use Modules\Blog\Http\Requests\BlogCategoryRequest;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;

class ArticleController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        // Дерево строит компонент: он же собирает поле порядка и обязан видеть
        // тот же список, поэтому раскладывать его здесь незачем
        $categories = Blog::query()
            ->orderBy('sort')
            ->with('new', 'lastArticle.user')
            ->get();

        // Счётчик раздела показывает всю ветку: вложенные статьи принадлежат
        // ему не меньше собственных
        CategoryTree::totals($categories, ['count_articles' => 'total_articles']);

        $new = Article::query()
            ->active(false)
            ->where('draft', false)
            ->count();

        return view('blog::admin/articles/index', compact('categories', 'new'));
    }

    /**
     * Сохраняет порядок и вложенность разделов
     */
    public function sort(Request $request): RedirectResponse
    {
        CategoryTree::reorder(Blog::class, $request->string('order')->toString());

        return redirect()
            ->route('admin.blogs.index')
            ->with('success', __('blog::blogs.categories_success_sorted'));
    }

    /**
     * Создание раздела
     */
    public function create(BlogCategoryRequest $request): RedirectResponse
    {
        $category = Blog::query()->create([
            'name' => $request->string('name')->value(),
            'sort' => Blog::query()->max('sort') + 1,
        ]);

        return redirect()->route('admin.blogs.edit', ['id' => $category->id])
            ->with('success', __('blog::blogs.category_success_created'));
    }

    /**
     * Редактирование раздела
     */
    public function edit(int $id): View
    {
        $category = $this->findCategory($id);
        $categories = $category->getChildren();

        return view('blog::admin/articles/edit', compact('categories', 'category'));
    }

    /**
     * Сохранение раздела
     */
    public function update(int $id, BlogCategoryRequest $request): RedirectResponse
    {
        $this->findCategory($id)->update([
            'parent_id' => $request->integer('parent'),
            'name'      => $request->string('name')->value(),
            'closed'    => $request->boolean('closed'),
        ]);

        return redirect()->route('admin.blogs.index')
            ->with('success', __('blog::blogs.category_success_edited'));
    }

    /**
     * Удаление раздела
     */
    public function delete(int $id): RedirectResponse
    {
        $category = $this->findCategory($id);
        $redirect = redirect()->route('admin.blogs.index');

        if ($category->children->isNotEmpty()) {
            return $redirect->withErrors(__('blog::blogs.category_has_subcategories'));
        }

        if (Article::query()->where('category_id', $category->id)->exists()) {
            return $redirect->withErrors(__('blog::blogs.articles_in_category'));
        }

        $category->delete();

        return $redirect->with('success', __('blog::blogs.category_success_deleted'));
    }

    /**
     * Пересчет данных
     */
    public function restatement(): RedirectResponse
    {
        Restatement::run('blogs');

        return redirect()
            ->route('admin.blogs.index')
            ->with('success', __('main.success_recounted'));
    }

    /**
     * Список блогов
     */
    public function blog(int $id): View
    {
        // Дерево целиком: счётчик подраздела складывает всю его ветку
        $categories = Blog::query()->orderBy('sort')->get();

        CategoryTree::totals($categories, ['count_articles' => 'total_articles']);
        CategoryTree::nest($categories);

        $category = $categories->firstWhere('id', $id);

        if (! $category) {
            abort(404, __('blog::blogs.category_not_exist'));
        }

        $articles = Article::query()
            ->active()
            ->where('category_id', $id)
            ->orderByDesc('created_at')
            ->with('user')
            ->paginate(setting('blogpost'));

        return view('blog::admin/articles/blog', compact('articles', 'category'));
    }

    /**
     * Редактирование статьи
     */
    public function editArticle(int $id): View
    {
        $article = $this->findArticle($id);
        $categories = $article->category->getChildren();

        return view('blog::admin/articles/edit_article', compact('article', 'categories'));
    }

    /**
     * Сохранение статьи
     */
    public function updateArticle(int $id, ArticleRequest $request): RedirectResponse
    {
        $article = $this->findArticle($id);

        $isPublish = $request->boolean('publish');
        $publishedAt = $request->publishedAt();
        $wasDraft = $article->draft;
        $isDraft = ! $isPublish && $article->draft;

        $article->update([
            'category_id'  => $request->category()->id,
            'title'        => $request->string('title')->value(),
            'text'         => $request->string('text')->value(),
            'draft'        => $isDraft,
            'active'       => $isPublish || (! $isDraft && ! $publishedAt && $article->active),
            'published_at' => $publishedAt,
        ]);

        $article->syncTags($request->tagNames());
        clearCache('tagCloud');

        $flash = ($wasDraft && $isPublish) ? __('blog::blogs.article_success_created') : __('blog::blogs.article_success_edited');

        return redirect()
            ->route('articles.view', ['slug' => $article->slug])
            ->with('success', $flash);
    }

    /**
     * Удаление статьи
     */
    public function deleteArticle(int $id, Request $request): RedirectResponse
    {
        $page = int($request->input('page', 1));

        $article = $this->findArticle($id);

        $article->delete();

        clearCache('tagCloud');

        return redirect()->route('admin.blogs.blog', ['id' => $article->category_id, 'page' => $page])
            ->with('success', __('blog::blogs.article_success_deleted'));
    }

    /**
     * Публикация статьи
     */
    public function publish(int $id): RedirectResponse
    {
        $article = $this->findArticle($id);

        $active = $article->active ^ 1;

        $article->update([
            'active'     => $active,
            'draft'      => false,
            'created_at' => now(),
        ]);

        if ($active) {
            $status = __('blog::blogs.article_success_published');
            $text = textNotice('article_publish', ['url' => route('articles.view', ['slug' => $article->slug], false), 'title' => $article->title]);
        } else {
            $status = __('blog::blogs.article_success_unpublished');
            $text = textNotice('article_unpublish', ['url' => route('articles.view', ['slug' => $article->slug], false), 'title' => $article->title]);
        }

        $article->user->sendMessage(null, $text);

        return redirect()
            ->route('admin.articles.edit', ['id' => $article->id])
            ->with('success', $status);
    }

    /**
     * Новые статьи
     */
    public function new(): View
    {
        $articles = Article::query()
            ->active(false)
            ->where('draft', false)
            ->orderByDesc('created_at')
            ->with('user', 'category')
            ->paginate(setting('blogpost'));

        return view('blog::admin/articles/new', compact('articles'));
    }

    /**
     * Раздел по id
     */
    private function findCategory(int $id): Blog
    {
        $category = Blog::query()->with('children')->find($id);

        if (! $category) {
            abort(404, __('blog::blogs.category_not_exist'));
        }

        return $category;
    }

    /**
     * Статья по id
     */
    private function findArticle(int $id): Article
    {
        $article = Article::query()->find($id);

        if (! $article) {
            abort(404, __('blog::blogs.article_not_exist'));
        }

        return $article;
    }
}
