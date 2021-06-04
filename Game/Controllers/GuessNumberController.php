<?php

declare(strict_types=1);

namespace Modules\Game\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuessNumberController extends Controller
{
    /**
     * @var User
     */
    private $user;

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
     *
     * @param Request $request
     *
     * @return View
     */
    public function index(Request $request): View
    {
        $newGame = int($request->input('new'));

        if ($newGame) {
            $request->session()->forget('guess');
        }

        return view('Game::guess/index', ['user' => $this->user]);
    }

    /**
     * Попытка
     *
     * @param Request   $request
     * @param Validator $validator
     *
     * @return View|RedirectResponse
     */
    public function go(Request $request, Validator $validator)
    {
        $guessNumber = int($request->input('guess'));

        $validator->equal($request->input('_token'), csrf_token(), __('validator.token'))
            ->between($guessNumber, 1, 100, ['guess' => 'Необходимо указать число!'])
            ->gte($this->user->money, 3, ['guess' => 'У вас недостаточно денег для игры!']);

        if (! $validator->isValid()) {
            setInput($request->all());
            setFlash('danger', $validator->getErrors());

            return redirect('games/guess');
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
                    $hint = 'большое число, введите меньше!';
                }

                if ($guessNumber < $guess['number']) {
                    $hint = 'маленькое число, введите больше!';
                }
            } else {
                $request->session()->forget('guess');
            }
        } else {
            $request->session()->forget('guess');
            $this->user->increment('money', 100);
        }

        $user = $this->user;

        return view('Game::guess/go', compact('user', 'guess', 'hint', 'guessNumber'));
    }
}
