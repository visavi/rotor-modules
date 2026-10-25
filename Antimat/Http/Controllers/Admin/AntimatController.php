<?php

declare(strict_types=1);

namespace Modules\Antimat\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Antimat\Http\Requests\AntimatWordRequest;
use Modules\Antimat\Models\Antimat;

class AntimatController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $words = Antimat::query()->get();

        return view('antimat::admin/index', compact('words'));
    }

    /**
     * Добавление слова в список
     */
    public function store(AntimatWordRequest $request): RedirectResponse
    {
        Antimat::query()->create([
            'string' => $request->string('word')->value(),
        ]);

        return redirect()->route('admin.antimat.index')
            ->with('success', __('main.record_added_success'));
    }

    /**
     * Удаление слова из списка
     */
    public function delete(Request $request): RedirectResponse
    {
        $word = Antimat::query()->find($request->integer('id'));

        if (! $word) {
            return redirect()->route('admin.antimat.index')
                ->withErrors(__('main.record_not_found'));
        }

        $word->delete();

        return redirect()->route('admin.antimat.index')
            ->with('success', __('main.record_deleted_success'));
    }

    /**
     * Очистка списка слов
     */
    public function clear(): RedirectResponse
    {
        Antimat::query()->delete();
        Antimat::flushCache();

        return redirect()->route('admin.antimat.index')
            ->with('success', __('main.records_cleared_success'));
    }
}
