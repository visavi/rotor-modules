<?php

declare(strict_types=1);

namespace Modules\Notebook\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Notebook\Http\Requests\NotebookRequest;
use Modules\Notebook\Models\Notebook;

class NotebookController extends Controller
{
    private Notebook $note;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->middleware('check.user');

        $this->middleware(function ($request, $next) {
            $user = auth()->user() ?? abort(403);

            $this->note = Notebook::query()
                ->where('user_id', $user->id)
                ->firstOrNew(['user_id' => $user->id]);

            return $next($request);
        });
    }

    /**
     * Главная страница
     */
    public function index(): View
    {
        return view('notebook::notebooks/index', ['note' => $this->note]);
    }

    /**
     * Редактирование
     */
    public function edit(): View
    {
        return view('notebook::notebooks/edit', ['note' => $this->note]);
    }

    /**
     * Сохранение
     */
    public function update(NotebookRequest $request): RedirectResponse
    {
        $this->note->fill(['text' => $request->string('text')->value()])->save();

        return redirect()->route('notebooks.index')
            ->with('success', __('main.record_saved_success'));
    }
}
