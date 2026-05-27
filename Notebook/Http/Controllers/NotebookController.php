<?php

declare(strict_types=1);

namespace Modules\Notebook\Http\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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
            $user = getUser();

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
    public function edit(Request $request, Validator $validator): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $msg = $request->input('msg');

            $validator->length($msg, 0, 10000, ['msg' => __('validator.text_long')], false);

            if ($validator->isValid()) {
                $this->note->fill([
                    'text'       => $msg,
                    'created_at' => SITETIME,
                ])->save();

                setFlash('success', __('main.record_saved_success'));
            } else {
                setInput($request->all());
                setFlash('danger', $validator->getErrors());
            }

            return redirect()->route('notebooks.index');
        }

        return view('notebook::notebooks/edit', ['note' => $this->note]);
    }
}
