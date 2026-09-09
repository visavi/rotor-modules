<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuessNumberController extends Controller
{
    /**
     * Текущий пользователь
     */
    private User $user;

    /**
     * DiceController constructor.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = getUser();

            return $next($request);
        });
    }

    /**
     * Угадай число
     */
    public function index(Request $request): View
    {
        $newGame = int($request->input('new'));

        if ($newGame) {
            $request->session()->forget('guess');
        }

        return view('game::guess/index', ['user' => $this->user]);
    }

    /**
     * Попытка
     */
    public function go(Request $request, Validator $validator): View|RedirectResponse
    {
        $guessNumber = int($request->input('guess'));

        $validator
            ->between($guessNumber, 1, 100, ['guess' => __('game::games.guess_number_required')])
            ->gte($this->user->money, 3, ['guess' => __('game::games.not_enough_money')]);

        if (! $validator->isValid()) {
            return redirect('games/guess')
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        if ($request->session()->missing('guess.number')) {
            $request->session()->put('guess.count', 0);
            $request->session()->put('guess.number', mt_rand(1, 100));
        }

        $request->session()->increment('guess.count');
        $this->user->decrement('money', 3);
        $hint = null;

        $guess = $request->session()->get('guess');

        if ($guessNumber !== $guess['number']) {
            if ($guess['count'] < 5) {
                if ($guessNumber > $guess['number']) {
                    $hint = __('game::games.guess_hint_less');
                }

                if ($guessNumber < $guess['number']) {
                    $hint = __('game::games.guess_hint_more');
                }
            } else {
                $request->session()->forget('guess');
            }
        } else {
            $request->session()->forget('guess');
            $this->user->increment('money', 100);
        }

        $user = $this->user;

        return view('game::guess/go', compact('user', 'guess', 'hint', 'guessNumber'));
    }
}
