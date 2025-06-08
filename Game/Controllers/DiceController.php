<?php

declare(strict_types=1);

namespace Modules\Game\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DiceController extends Controller
{
    private User $user;

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
     * Кости
     */
    public function index(): View
    {
        return view('game::dices/index', ['user' => $this->user]);
    }

    /**
     * Игра в кости
     */
    public function go(): View
    {
        if ($this->user->money < 5) {
            abort(200, 'Вы не можете играть! У вас недостаточно средств!');
        }

        $results = [
            'victory' => '<span class="text-success">Вы выиграли</span>',
            'lost'    => '<span class="text-danger">Вы проиграли</span>',
            'draw'    => 'Ничья',
        ];

        $num[0] = mt_rand(1, mt_rand(5, 6));
        $num[1] = mt_rand(1, mt_rand(5, 6));
        $num[2] = mt_rand(1, 6);
        $num[3] = mt_rand(1, 6);

        $sumUser = $num[0] + $num[1];
        $sumBank = $num[2] + $num[3];

        if ($sumUser > $sumBank) {
            $this->user->increment('money', 10);
            $result = $results['victory'];
        } elseif ($sumUser < $sumBank) {
            $this->user->decrement('money', 5);
            $result = $results['lost'];
        } else {
            $result = $results['draw'];
        }

        $user = $this->user;

        return view('game::dices/go', compact('num', 'result', 'user'));
    }
}
