<?php

declare(strict_types=1);

namespace Modules\Game\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThimbleController extends Controller
{
    /**
     * @var User
     */
    private $user;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = getUser();

            return $next($request);
        });
    }

    /**
     * Наперстки
     *
     * @return View
     */
    public function index(): View
    {
        return view('Game::thimbles/index', ['user' => $this->user]);
    }

    /**
     * Выбор наперстка
     *
     * @return View
     */
    public function choice(): View
    {
        return view('Game::thimbles/choice', ['user' => $this->user]);
    }

    /**
     * Игра в наперстки
     *
     * @param Request $request
     *
     * @return View|RedirectResponse
     */
    public function go(Request $request)
    {
        $thimble = int($request->input('thimble'));

        if ($this->user->money < 5) {
            abort(200, 'Вы не можете играть! У вас недостаточно средств!');
        }

        if (! $thimble) {
            setFlash('danger', 'Необходимо выбрать один из наперстков!');
            return redirect('games/thimbles/choice');
        }

        $results = [
            'victory' => '<span class="text-success">Вы выиграли</span>',
            'lost'    => '<span class="text-danger">Вы проиграли</span>',
        ];

        $randThimble = mt_rand(1, 3);

        if ($thimble === $randThimble) {
            $this->user->increment('money', 10);
            $result = $results['victory'];
        } else {
            $this->user->decrement('money', 5);
            $result = $results['lost'];
        }

        $user = $this->user;

        return view('Game::thimbles/go', compact('user', 'randThimble', 'thimble', 'result'));
    }
}
