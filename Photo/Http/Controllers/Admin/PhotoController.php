<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Support\Restatement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Photo\Http\Requests\PhotoRequest;
use Modules\Photo\Models\Photo;

class PhotoController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $photos = Photo::query()
            ->orderByDesc('created_at')
            ->with('user', 'files')
            ->paginate(setting('fotolist'));

        return view('photo::admin/photos/index', compact('photos'));
    }

    /**
     * Редактирование фото
     */
    public function edit(int $id, Request $request): View
    {
        $page = int($request->input('page', 1));
        $photo = $this->findPhoto($id);

        return view('photo::admin/photos/edit', compact('photo', 'page'));
    }

    /**
     * Сохранение фото
     */
    public function update(int $id, PhotoRequest $request): RedirectResponse
    {
        $this->findPhoto($id)->update($request->photoFields());

        clearCache(['statPhotos', 'recentPhotos']);

        return redirect()->route('admin.photos.index', ['page' => int($request->input('page', 1))])
            ->with('success', __('photo::photos.photo_success_edited'));
    }

    /**
     * Удаление записей
     */
    public function delete(int $id, Request $request): RedirectResponse
    {
        if (! is_writable(public_path('uploads/photos'))) {
            abort(200, __('main.directory_not_writable'));
        }

        $page = int($request->input('page', 1));

        $this->findPhoto($id)->delete();

        clearCache(['statPhotos', 'recentPhotos']);

        return redirect()->route('admin.photos.index', ['page' => $page])
            ->with('success', __('photo::photos.photo_success_deleted'));
    }

    /**
     * Пересчет комментариев
     */
    public function restatement(): RedirectResponse
    {
        Restatement::run('photos');

        return redirect()
            ->route('admin.photos.index')
            ->with('success', __('main.success_recounted'));
    }

    /**
     * Фото по id
     */
    private function findPhoto(int $id): Photo
    {
        $photo = Photo::query()->find($id);

        if (! $photo) {
            abort(404, __('photo::photos.photo_not_exist'));
        }

        return $photo;
    }
}
