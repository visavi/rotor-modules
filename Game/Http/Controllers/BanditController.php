<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class BanditController extends Controller
{
    /**
     * Текущий пользователь
     */
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
     * Бандит
     */
    public function index(): View
    {
        return view('game::bandit/index', ['user' => $this->user]);
    }

    /**
     * Игра
     */
    public function go(): View
    {
        if ($this->user->money < 5) {
            abort(200, __('game::games.cannot_play'));
        }

        $num[1] = mt_rand(1, 8);
        $num[2] = mt_rand(1, 8);
        $num[3] = mt_rand(1, 8);
        $num[4] = mt_rand(1, 8);
        $num[5] = mt_rand(1, mt_rand(7, 8));
        $num[6] = mt_rand(1, 8);
        $num[7] = mt_rand(1, 8);
        $num[8] = mt_rand(1, 8);
        $num[9] = mt_rand(1, 8);

        $sum = 0;
        $results = [];

        // ряды
        if ($num[1] === 1 && $num[2] === 1 && $num[3] === 1) {
            $results[] = $this->line('cherry', 'top_row');
            $sum += 5;
        }
        if ($num[4] === 1 && $num[5] === 1 && $num[6] === 1) {
            $results[] = $this->line('cherry', 'middle_row');
            $sum += 10;
        }
        if ($num[7] === 1 && $num[8] === 1 && $num[9] === 1) {
            $results[] = $this->line('cherry', 'bottom_row');
            $sum += 5;
        }

        if ($num[1] === 2 && $num[2] === 2 && $num[3] === 2) {
            $results[] = $this->line('orange', 'top_row');
            $sum += 10;
        }
        if ($num[4] === 2 && $num[5] === 2 && $num[6] === 2) {
            $results[] = $this->line('orange', 'middle_row');
            $sum += 15;
        }
        if ($num[7] === 2 && $num[8] === 2 && $num[9] === 2) {
            $results[] = $this->line('orange', 'bottom_row');
            $sum += 10;
        }

        if ($num[1] === 3 && $num[2] === 3 && $num[3] === 3) {
            $results[] = $this->line('grape', 'top_row');
            $sum += 15;
        }
        if ($num[4] === 3 && $num[5] === 3 && $num[6] === 3) {
            $results[] = $this->line('grape', 'middle_row');
            $sum += 25;
        }
        if ($num[7] === 3 && $num[8] === 3 && $num[9] === 3) {
            $results[] = $this->line('grape', 'bottom_row');
            $sum += 15;
        }

        if ($num[1] === 4 && $num[2] === 4 && $num[3] === 4) {
            $results[] = $this->line('banana', 'top_row');
            $sum += 25;
        }
        if ($num[4] === 4 && $num[5] === 4 && $num[6] === 4) {
            $results[] = $this->line('banana', 'middle_row');
            $sum += 35;
        }
        if ($num[7] === 4 && $num[8] === 4 && $num[9] === 4) {
            $results[] = $this->line('banana', 'bottom_row');
            $sum += 25;
        }

        if ($num[1] === 5 && $num[2] === 5 && $num[3] === 5) {
            $results[] = $this->line('apple', 'top_row');
            $sum += 30;
        }
        if ($num[4] === 5 && $num[5] === 5 && $num[6] === 5) {
            $results[] = $this->line('apple', 'middle_row');
            $sum += 50;
        }
        if ($num[7] === 5 && $num[8] === 5 && $num[9] === 5) {
            $results[] = $this->line('apple', 'bottom_row');
            $sum += 30;
        }

        if ($num[1] === 6 && $num[2] === 6 && $num[3] === 6) {
            $results[] = $this->line('bar', 'top_row');
            $sum += 50;
        }
        if ($num[4] === 6 && $num[5] === 6 && $num[6] === 6) {
            $results[] = $this->line('bar', 'middle_row');
            $sum += 70;
        }
        if ($num[7] === 6 && $num[8] === 6 && $num[9] === 6) {
            $results[] = $this->line('bar', 'bottom_row');
            $sum += 50;
        }

        if ($num[1] === 7 && $num[2] === 7 && $num[3] === 7) {
            $results[] = $this->line('dollar', 'top_row');
            $sum += 60;
        }
        if ($num[4] === 7 && $num[5] === 7 && $num[6] === 7) {
            $results[] = $this->line('dollar', 'middle_row');
            $sum += 100;
        }
        if ($num[7] === 7 && $num[8] === 7 && $num[9] === 7) {
            $results[] = $this->line('dollar', 'bottom_row');
            $sum += 60;
        }

        if ($num[1] === 8 && $num[2] === 8 && $num[3] === 8) {
            $results[] = $this->line('seven', 'top_row');
            $sum += 177;
        }
        if ($num[4] === 8 && $num[5] === 8 && $num[6] === 8) {
            $results[] = $this->line('seven', 'middle_row');
            $sum += 777;
        }
        if ($num[7] === 8 && $num[8] === 8 && $num[9] === 8) {
            $results[] = $this->line('seven', 'bottom_row');
            $sum += 177;
        }

        // столбцы
        if ($num[1] === 1 && $num[4] === 1 && $num[7] === 1) {
            $results[] = $this->line('cherry', 'left_column');
            $sum += 5;
        }
        if ($num[2] === 1 && $num[5] === 1 && $num[8] === 1) {
            $results[] = $this->line('cherry', 'middle_column');
            $sum += 10;
        }
        if ($num[3] === 1 && $num[6] === 1 && $num[9] === 1) {
            $results[] = $this->line('cherry', 'right_column');
            $sum += 5;
        }

        if ($num[1] === 2 && $num[4] === 2 && $num[7] === 2) {
            $results[] = $this->line('orange', 'left_column');
            $sum += 10;
        }
        if ($num[2] === 2 && $num[5] === 2 && $num[8] === 2) {
            $results[] = $this->line('orange', 'middle_column');
            $sum += 15;
        }
        if ($num[3] === 2 && $num[6] === 2 && $num[9] === 2) {
            $results[] = $this->line('orange', 'right_column');
            $sum += 10;
        }

        if ($num[1] === 3 && $num[4] === 3 && $num[7] === 3) {
            $results[] = $this->line('grape', 'left_column');
            $sum += 15;
        }
        if ($num[2] === 3 && $num[5] === 3 && $num[8] === 3) {
            $results[] = $this->line('grape', 'middle_column');
            $sum += 25;
        }
        if ($num[3] === 3 && $num[6] === 3 && $num[9] === 3) {
            $results[] = $this->line('grape', 'right_column');
            $sum += 15;
        }

        if ($num[1] === 4 && $num[4] === 4 && $num[7] === 4) {
            $results[] = $this->line('banana', 'left_column');
            $sum += 25;
        }
        if ($num[2] === 4 && $num[5] === 4 && $num[8] === 4) {
            $results[] = $this->line('banana', 'middle_column');
            $sum += 35;
        }
        if ($num[3] === 4 && $num[6] === 4 && $num[9] === 4) {
            $results[] = $this->line('banana', 'right_column');
            $sum += 25;
        }

        if ($num[1] === 5 && $num[4] === 5 && $num[7] === 5) {
            $results[] = $this->line('apple', 'left_column');
            $sum += 30;
        }
        if ($num[2] === 5 && $num[5] === 5 && $num[8] === 5) {
            $results[] = $this->line('apple', 'middle_column');
            $sum += 50;
        }
        if ($num[3] === 5 && $num[6] === 5 && $num[9] === 5) {
            $results[] = $this->line('apple', 'right_column');
            $sum += 30;
        }

        if ($num[1] === 6 && $num[4] === 6 && $num[7] === 6) {
            $results[] = $this->line('bar', 'left_column');
            $sum += 50;
        }
        if ($num[2] === 6 && $num[5] === 6 && $num[8] === 6) {
            $results[] = $this->line('bar', 'middle_column');
            $sum += 70;
        }
        if ($num[3] === 6 && $num[6] === 6 && $num[9] === 6) {
            $results[] = $this->line('bar', 'right_column');
            $sum += 50;
        }

        if ($num[1] === 7 && $num[4] === 7 && $num[7] === 7) {
            $results[] = $this->line('dollar', 'left_column');
            $sum += 60;
        }
        if ($num[2] === 7 && $num[5] === 7 && $num[8] === 7) {
            $results[] = $this->line('dollar', 'middle_column');
            $sum += 100;
        }
        if ($num[3] === 7 && $num[6] === 7 && $num[9] === 7) {
            $results[] = $this->line('dollar', 'right_column');
            $sum += 60;
        }

        if ($num[1] === 8 && $num[4] === 8 && $num[7] === 8) {
            $results[] = $this->line('seven', 'left_column');
            $sum += 100;
        }
        if ($num[2] === 8 && $num[5] === 8 && $num[8] === 8) {
            $results[] = $this->line('seven', 'middle_column');
            $sum += 177;
        }
        if ($num[3] === 8 && $num[6] === 8 && $num[9] === 8) {
            $results[] = $this->line('seven', 'right_column');
            $sum += 100;
        }

        $this->user->decrement('money', 5);

        if ($sum > 0) {
            $this->user->increment('money', $sum);
        }

        $user = $this->user;

        return view('game::bandit/go', compact('num', 'results', 'sum', 'user'));
    }

    /**
     * Правила игры
     */
    public function faq(): View
    {
        return view('game::bandit/faq');
    }

    /**
     * Собирает подпись выигрышной линии
     */
    private function line(string $symbol, string $position): string
    {
        return __('game::games.line', [
            'symbol'   => __('game::games.symbols.' . $symbol),
            'position' => __('game::games.positions.' . $position),
        ]);
    }
}
