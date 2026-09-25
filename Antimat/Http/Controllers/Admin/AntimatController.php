<?php

declare(strict_types=1);

namespace Modules\Antimat\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
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
    public function store(Request $request, Validator $validator): RedirectResponse
    {
        $word = Str::lower((string) $request->input('word'));

        $validator->notEmpty($word, __('antimat::antimat.not_enter_word'))
            ->length($word, 0, 100, ['word' => __('validator.text')]);

        $duplicate = Antimat::query()->where('string', $word)->first();
        $validator->empty($duplicate, __('antimat::antimat.word_listed'));

        if (! $validator->isValid()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        Antimat::query()->create([
            'string' => $word,
        ]);

        return redirect()->route('admin.antimat.index')
            ->with('success', __('main.record_added_success'));
    }

    /**
     * Удаление слова из списка
     */
    public function delete(Request $request, Validator $validator): RedirectResponse
    {
        $id = int($request->input('id'));

        $word = Antimat::query()->find($id);
        $validator->notEmpty($word, __('main.record_not_found'));

        if (! $validator->isValid()) {
            return redirect()->route('admin.antimat.index')
                ->withErrors($validator->getErrors());
        }

        $word->delete();

        return redirect()->route('admin.antimat.index')
            ->with('success', __('main.record_deleted_success'));
    }

    /**
     * Очистка списка слов
     */
    public function clear(Validator $validator): RedirectResponse
    {
        $validator->true(isAdmin(User::BOSS), __('main.page_only_owner'));

        if (! $validator->isValid()) {
            return redirect()->route('admin.antimat.index')
                ->withErrors($validator->getErrors());
        }

        Antimat::query()->delete();
        Antimat::flushCache();

        return redirect()->route('admin.antimat.index')
            ->with('success', __('main.records_cleared_success'));
    }
}
