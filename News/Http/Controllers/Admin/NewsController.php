<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\File;
use App\Services\FileService;
use App\Support\Restatement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\News\Http\Requests\NewsRequest;
use Modules\News\Models\News;

class NewsController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $news = News::query()
            ->orderByDesc('created_at')
            ->with('user')
            ->paginate(setting('postnews'));

        return view('news::admin/news/index', compact('news'));
    }

    /**
     * Редактирование новости
     */
    public function edit(int $id, Request $request): View
    {
        $news = $this->findNews($id);
        $page = int($request->input('page', 1));

        return view('news::admin/news/edit', compact('news', 'page'));
    }

    /**
     * Сохранение новости
     */
    public function update(int $id, NewsRequest $request): RedirectResponse
    {
        $news = $this->findNews($id);

        $news->update([
            'title'  => $request->string('title')->value(),
            'text'   => $request->string('text')->value(),
            'closed' => $request->boolean('closed'),
            'pinned' => $request->boolean('pinned'),
        ]);

        clearCache(['statNews', 'pinnedNews', 'statNewsDate']);

        return redirect()->route('news.view', ['id' => $news->id])
            ->with('success', __('news::news.news_success_edited'));
    }

    /**
     * Создание новости
     */
    public function create(): View
    {
        $files = File::query()
            ->pending(News::$morphName, getUser('id'))
            ->ordered()
            ->get();

        return view('news::admin/news/create', compact('files'));
    }

    /**
     * Публикация новости
     */
    public function store(NewsRequest $request, FileService $files): RedirectResponse
    {
        $news = News::query()->create([
            'user_id' => getUser('id'),
            'title'   => $request->string('title')->value(),
            'text'    => $request->string('text')->value(),
            'closed'  => $request->boolean('closed'),
            'pinned'  => $request->boolean('pinned'),
        ]);

        // Файлы загружаются в редактор до публикации
        $files->attach($news, []);

        clearCache(['statNews', 'pinnedNews', 'statNewsDate']);

        return redirect()->route('news.view', ['id' => $news->id])
            ->with('success', __('news::news.news_success_added'));
    }

    /**
     * Пересчет комментариев
     */
    public function restatement(): RedirectResponse
    {
        Restatement::run('news');

        return redirect()
            ->route('admin.news.index')
            ->with('success', __('main.success_recounted'));
    }

    /**
     * Удаление новостей
     */
    public function delete(int $id, Request $request): RedirectResponse
    {
        $page = int($request->input('page', 1));

        $this->findNews($id)->delete();

        clearCache(['statNews', 'pinnedNews', 'statNewsDate']);

        return redirect()->route('admin.news.index', ['page' => $page])
            ->with('success', __('news::news.news_success_deleted'));
    }

    /**
     * Новость по id
     */
    private function findNews(int $id): News
    {
        $news = News::query()->find($id);

        if (! $news) {
            abort(404, __('news::news.news_not_exist'));
        }

        return $news;
    }
}
