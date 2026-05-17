<?php

declare(strict_types=1);

namespace Modules\Photo\Controllers\Admin;

use App\Classes\Restatement;
use App\Classes\Validator;
use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Photo\Models\Photo;

class PhotoController extends AdminController
{
    public function index(): View
    {
        $photos = Photo::query()
            ->orderByDesc('created_at')
            ->with('user', 'files')
            ->paginate(setting('fotolist'));

        return view('photo::admin/photos/index', compact('photos'));
    }

    public function edit(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $page = int($request->input('page', 1));
        $photo = Photo::query()->find($id);

        if (! $photo) {
            abort(404, __('photo::photos.photo_not_exist'));
        }

        if ($request->isMethod('post')) {
            $title = $request->input('title');
            $text = $request->input('text');
            $closed = empty($request->input('closed')) ? 0 : 1;

            $validator
                ->length($title, setting('photo_title_min'), setting('photo_title_max'), ['title' => __('validator.text')])
                ->length($text, setting('photo_text_min'), setting('photo_text_max'), ['text' => __('validator.text_long')]);

            if ($validator->isValid()) {
                $text = antimat($text);

                $photo->update([
                    'title'  => $title,
                    'text'   => $text,
                    'closed' => $closed,
                ]);

                clearCache(['statPhotos', 'recentPhotos']);
                setFlash('success', __('photo::photos.photo_success_edited'));

                return redirect()->route('admin.photos.index', ['page' => $page]);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        return view('photo::admin/photos/edit', compact('photo', 'page'));
    }

    public function delete(int $id, Request $request): RedirectResponse
    {
        if (! is_writable(public_path('uploads/photos'))) {
            abort(200, __('main.directory_not_writable'));
        }

        $page = int($request->input('page', 1));

        $photo = Photo::query()->find($id);

        if (! $photo) {
            abort(404, __('photo::photos.photo_not_exist'));
        }

        $photo->delete();

        clearCache(['statPhotos', 'recentPhotos']);
        setFlash('success', __('photo::photos.photo_success_deleted'));

        return redirect()->route('admin.photos.index', ['page' => $page]);
    }

    public function restatement(): RedirectResponse
    {
        if (! isAdmin(User::BOSS)) {
            abort(200, __('main.page_only_owner'));
        }

        Restatement::run('photos');

        return redirect()
            ->route('admin.photos.index')
            ->with('success', __('main.success_recounted'));
    }
}
