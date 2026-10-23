<?php

declare(strict_types=1);

namespace Modules\Advert\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Advert\Http\Requests\AdvertRequest;
use Modules\Advert\Models\Advert;

class AdminAdvertController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $advert = Advert::query()
            ->where('type', Advert::TYPE_ADMIN)
            ->where('user_id', auth()->id())
            ->firstOrNew();

        return view('advert::admin/admin-adverts/index', compact('advert'));
    }

    /**
     * Сохранение своей ссылки
     */
    public function store(AdvertRequest $request): RedirectResponse
    {
        Advert::query()->updateOrCreate([
            'type'    => Advert::TYPE_ADMIN,
            'user_id' => auth()->id(),
        ], $request->advertFields() + [
            'deleted_at' => now()->addDays((int) setting('rekadmintime')),
        ]);

        return redirect('admin/admin-adverts')->with('success', __('main.record_saved_success'));
    }

    /**
     * Удаление записи
     */
    public function delete(): RedirectResponse
    {
        $advert = Advert::query()
            ->where('type', Advert::TYPE_ADMIN)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $advert->delete();

        return redirect('admin/admin-adverts')->with('success', __('main.record_deleted_success'));
    }
}
