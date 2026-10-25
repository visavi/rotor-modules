<?php

declare(strict_types=1);

namespace Modules\Advert\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\DeleteSelectedRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Advert\Http\Requests\AdvertRequest;
use Modules\Advert\Models\Advert;

class AdvertController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $records = Advert::query()
            ->where('type', Advert::TYPE_USER)
            ->where('deleted_at', '>', now())
            ->orderByDesc('deleted_at')
            ->with('user')
            ->paginate(setting('rekuserpost'));

        return view('advert::admin/adverts/index', compact('records'));
    }

    /**
     * Редактирование ссылки
     */
    public function edit(int $id, Request $request): View
    {
        $page = $request->integer('page', 1);
        $link = $this->findLink($id);

        return view('advert::admin/adverts/edit', compact('link', 'page'));
    }

    /**
     * Сохранение ссылки
     */
    public function update(int $id, AdvertRequest $request): RedirectResponse
    {
        $link = $this->findLink($id);

        $link->update($request->advertFields());

        return redirect('admin/adverts?page=' . $request->integer('page', 1))
            ->with('success', __('main.record_changed_success'));
    }

    /**
     * Удаление записей
     */
    public function delete(DeleteSelectedRequest $request): RedirectResponse
    {
        Advert::query()->whereIn('id', $request->ids())->delete();

        clearCache('adverts');

        return redirect('admin/adverts?page=' . $request->integer('page', 1))
            ->with('success', __('main.records_deleted_success'));
    }

    /**
     * Ссылка по id
     */
    private function findLink(int $id): Advert
    {
        $link = Advert::query()->findOrFail($id);

        return $link;
    }
}
