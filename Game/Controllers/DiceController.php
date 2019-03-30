<?php

declare(strict_types=1);

namespace App\Modules\Game\Controllers;

use App\Controllers\BaseController;
use App\Models\User;

class DiceController extends BaseController
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
        parent::__construct();

        if (! $this->user = getUser()) {
            abort(403, 'Для игры необходимо авторизоваться!');
        }
    }

    /**
     * Кости
     *
     * @return string
     */
    public function index(): string
    {
        return view('Game::dices/index', ['user' => $this->user]);
    }

    /**
     * Игра в кости
     *
     * @return string
     */
    public function go(): string
    {
        if ($this->user->money < 5) {
            abort('default', 'Вы не можете играть! У вас недостаточно средств!');
        }

        $results = [
            'victory' => '<span class="text-success">Вы выиграли</span>',
            'lost'    => '<span class="text-danger">Вы проиграли</span>',
            'draw'    => 'Ничья',
        ];

        $num[0] = \mt_rand(1, \mt_rand(5, 6));
        $num[1] = \mt_rand(1, \mt_rand(5, 6));
        $num[2] = \mt_rand(1, 6);
        $num[3] = \mt_rand(1, 6);

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

        return view('Game::dices/go', compact('num', 'result', 'user'));
    }
}
