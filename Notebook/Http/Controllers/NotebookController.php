<?php

declare(strict_types=1);

namespace Modules\Notebook\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Notebook\Http\Requests\NotebookRequest;
use Modules\Notebook\Models\Notebook;

class NotebookController extends Controller
{
    /**
     * Главная страница
     */
    public function index(#[CurrentUser] User $user): View
    {
        return view('notebook::notebooks/index', ['note' => $this->note($user)]);
    }

    /**
     * Редактирование
     */
    public function edit(#[CurrentUser] User $user): View
    {
        return view('notebook::notebooks/edit', ['note' => $this->note($user)]);
    }

    /**
     * Сохранение
     */
    public function update(NotebookRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $this->note($user)->fill(['text' => $request->string('text')->value()])->save();

        return redirect()->route('notebooks.index')
            ->with('success', __('main.record_saved_success'));
    }

    /**
     * Блокнот пользователя, пустой — если ещё не заведён
     */
    private function note(User $user): Notebook
    {
        return Notebook::query()->firstOrNew(['user_id' => $user->id]);
    }
}
