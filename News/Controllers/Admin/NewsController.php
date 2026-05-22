<?php

declare(strict_types=1);

namespace Modules\News\Controllers\Admin;

use App\Classes\Restatement;
use App\Classes\Validator;
use App\Http\Controllers\Admin\AdminController;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\News\Models\News;

class NewsController extends AdminController
{
    public function index(): View
    {
        $news = News::query()
            ->orderByDesc('created_at')
            ->with('user')
            ->paginate(setting('postnews'));

        return view('news::admin/news/index', compact('news'));
    }

    public function edit(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $news = News::query()->find($id);
        $page = int($request->input('page', 1));

        if (! $news) {
            abort(404, __('news::news.news_not_exist'));
        }

        if ($request->isMethod('post')) {
            $title = $request->input('title');
            $text = $request->input('text');
            $closed = empty($request->input('closed')) ? 0 : 1;
            $top = empty($request->input('top')) ? 0 : 1;

            $validator
                ->length($title, setting('news_title_min'), setting('news_title_max'), ['title' => __('validator.text')])
                ->length($text, setting('news_text_min'), setting('news_text_max'), ['text' => __('validator.text')]);

            if ($validator->isValid()) {
                $news->update([
                    'title'  => $title,
                    'text'   => $text,
                    'closed' => $closed,
                    'top'    => $top,
                ]);

                clearCache(['statNews', 'pinnedNews']);
                setFlash('success', __('news::news.news_success_edited'));

                return redirect()->route('news.view', ['id' => $news->id]);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        return view('news::admin/news/edit', compact('news', 'page'));
    }

    public function create(Request $request, Validator $validator): View|RedirectResponse
    {
        $files = File::query()
            ->where('relate_type', News::$morphName)
            ->where('relate_id', 0)
            ->where('user_id', getUser('id'))
            ->orderBy('created_at');

        if ($request->isMethod('post')) {
            $title = $request->input('title');
            $text = $request->input('text');
            $closed = empty($request->input('closed')) ? 0 : 1;
            $top = empty($request->input('top')) ? 0 : 1;

            $validator
                ->length($title, setting('news_title_min'), setting('news_title_max'), ['title' => __('validator.text')])
                ->length($text, setting('news_text_min'), setting('news_text_max'), ['text' => __('validator.text')]);

            if ($validator->isValid()) {
                $news = News::query()->create([
                    'user_id'    => getUser('id'),
                    'title'      => $title,
                    'text'       => $text,
                    'closed'     => $closed,
                    'top'        => $top,
                    'created_at' => SITETIME,
                ]);

                $files->update(['relate_id' => $news->id]);

                clearCache(['statNews', 'pinnedNews', 'statNewsDate']);
                setFlash('success', __('news::news.news_success_added'));

                return redirect()->route('news.view', ['id' => $news->id]);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        $files = $files->orderBy('created_at')->get();

        return view('news::admin/news/create', compact('files'));
    }

    public function restatement(): RedirectResponse
    {
        if (! isAdmin(User::BOSS)) {
            abort(403, __('errors.forbidden'));
        }

        Restatement::run('news');

        return redirect()
            ->route('admin.news.index')
            ->with('success', __('main.success_recounted'));
    }

    public function delete(int $id, Request $request): RedirectResponse
    {
        $page = int($request->input('page', 1));

        $news = News::query()->find($id);

        if (! $news) {
            abort(404, __('news::news.news_not_exist'));
        }

        $news->delete();

        setFlash('success', __('news::news.news_success_deleted'));

        return redirect()->route('admin.news.index', ['page' => $page]);
    }
}
