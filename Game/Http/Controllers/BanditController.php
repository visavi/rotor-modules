<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BanditController extends Controller
{
    /**
     * Цена одного вращения
     */
    public const BET = 5;

    /**
     * Сколько символов на барабане
     */
    public const SYMBOLS = 8;

    /**
     * Шанс выигрышного вращения в тысячных долях
     *
     * Автомат сначала решает, выиграет ли игрок, и только потом
     * раскладывает символы. На честных барабанах линия собиралась в 9%
     * вращений, и деньги уходили десятью спинами подряд без единой выплаты
     */
    public const WIN_CHANCE = 475;

    /**
     * Вес символа при выигрыше: чем дороже символ, тем реже он выпадает
     *
     * Веса подобраны так, чтобы средняя выплата за выигрышное вращение
     * равнялась двум ставкам — вместе с шансом это даёт возврат 95%.
     * Править их на глаз нельзя, возврат проверяет тест
     */
    public const WEIGHTS = [
        1 => 776,
        2 => 276,
        3 => 118,
        4 => 25,
        5 => 9,
        6 => 3,
        7 => 1,
        8 => 1,
    ];

    /**
     * Названия символов по их номеру
     */
    public const NAMES = [
        1 => 'cherry',
        2 => 'orange',
        3 => 'grape',
        4 => 'lemon',
        5 => 'apple',
        6 => 'bar',
        7 => 'dollar',
        8 => 'seven',
    ];

    /**
     * Выигрышные линии: подпись позиции и номера ячеек поля 3x3
     */
    public const LINES = [
        'top_row'       => [1, 2, 3],
        'middle_row'    => [4, 5, 6],
        'bottom_row'    => [7, 8, 9],
        'left_column'   => [1, 4, 7],
        'middle_column' => [2, 5, 8],
        'right_column'  => [3, 6, 9],
    ];

    /**
     * Выплаты: символ => позиция => выигрыш
     *
     * Крайние ряды и столбцы платят меньше средних, семёрки по ряду дороже,
     * чем по столбцу — таблица досталась от прежней версии игры без изменений
     */
    public const PAYOUTS = [
        1 => ['top_row' => 5, 'middle_row' => 10, 'bottom_row' => 5, 'left_column' => 5, 'middle_column' => 10, 'right_column' => 5],
        2 => ['top_row' => 10, 'middle_row' => 15, 'bottom_row' => 10, 'left_column' => 10, 'middle_column' => 15, 'right_column' => 10],
        3 => ['top_row' => 15, 'middle_row' => 25, 'bottom_row' => 15, 'left_column' => 15, 'middle_column' => 25, 'right_column' => 15],
        4 => ['top_row' => 25, 'middle_row' => 35, 'bottom_row' => 25, 'left_column' => 25, 'middle_column' => 35, 'right_column' => 25],
        5 => ['top_row' => 30, 'middle_row' => 50, 'bottom_row' => 30, 'left_column' => 30, 'middle_column' => 50, 'right_column' => 30],
        6 => ['top_row' => 50, 'middle_row' => 70, 'bottom_row' => 50, 'left_column' => 50, 'middle_column' => 70, 'right_column' => 50],
        7 => ['top_row' => 60, 'middle_row' => 100, 'bottom_row' => 60, 'left_column' => 60, 'middle_column' => 100, 'right_column' => 60],
        8 => ['top_row' => 177, 'middle_row' => 777, 'bottom_row' => 177, 'left_column' => 100, 'middle_column' => 177, 'right_column' => 100],
    ];

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
        return view('game::bandit/index', [
            'user' => $this->user,
            'spin' => null,
        ]);
    }

    /**
     * Вращение
     */
    public function spin(Request $request): View|RedirectResponse|JsonResponse
    {
        if ($this->user->money < self::BET) {
            abort(200, __('game::games.cannot_play'));
        }

        $before = $this->user->money;
        $cells = $this->roll();
        [$results, $sum] = $this->score($cells);

        $this->user->decrement('money', self::BET);

        if ($sum > 0) {
            $this->user->increment('money', $sum);
        }

        $spin = [
            'before'  => $before,
            'cells'   => $cells,
            'results' => $results,
            'sum'     => $sum,
        ];

        $data = ['user' => $this->user, 'spin' => $spin];

        // Вращение приходит ajax-ом: отдаём только автомат, чтобы страница не перезагружалась
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::bandit/_machine', $data)->render(),
            ]);
        }

        return view('game::bandit/index', $data);
    }

    /**
     * Правила игры
     */
    public function faq(): View
    {
        return view('game::bandit/faq');
    }

    /**
     * Крутит барабаны
     *
     * Сначала решается исход вращения, затем под него собирается поле:
     * выигрышное — с одной линией, проигрышное — вовсе без линий
     */
    private function roll(): array
    {
        if (random_int(1, 1000) > self::WIN_CHANCE) {
            return $this->losingCells();
        }

        return $this->winningCells($this->symbol(), array_rand(self::LINES));
    }

    /**
     * Символ выигрышной линии по весам
     */
    private function symbol(): int
    {
        $point = random_int(1, array_sum(self::WEIGHTS));

        foreach (self::WEIGHTS as $symbol => $weight) {
            $point -= $weight;

            if ($point <= 0) {
                return $symbol;
            }
        }

        return array_key_first(self::WEIGHTS);
    }

    /**
     * Поле с одной выигрышной линией
     *
     * Остальные ячейки заполняются заново, пока не перестанут складываться
     * в лишние линии: вторая линия ломала бы расчётную выплату
     */
    private function winningCells(int $symbol, string $position): array
    {
        $line = self::LINES[$position];

        do {
            $cells = [];

            foreach (range(1, 9) as $cell) {
                $cells[$cell] = in_array($cell, $line, true)
                    ? $symbol
                    : random_int(1, self::SYMBOLS);
            }
        } while (count($this->lines($cells)) !== 1);

        return $cells;
    }

    /**
     * Поле без единой линии
     */
    private function losingCells(): array
    {
        do {
            $cells = [];

            foreach (range(1, 9) as $cell) {
                $cells[$cell] = random_int(1, self::SYMBOLS);
            }
        } while ($this->lines($cells));

        return $cells;
    }

    /**
     * Позиции линий, собравшихся на поле
     *
     * @return list<string>
     */
    private function lines(array $cells): array
    {
        $found = [];

        foreach (self::LINES as $position => $line) {
            $symbol = $cells[$line[0]];

            foreach ($line as $cell) {
                if ($cells[$cell] !== $symbol) {
                    continue 2;
                }
            }

            $found[] = $position;
        }

        return $found;
    }

    /**
     * Считает выигрышные линии
     */
    private function score(array $cells): array
    {
        $results = [];
        $sum = 0;

        foreach (self::LINES as $position => $line) {
            $symbol = $cells[$line[0]];

            foreach ($line as $cell) {
                if ($cells[$cell] !== $symbol) {
                    continue 2;
                }
            }

            $results[] = [
                'position' => $position,
                'line'     => $line,
                'text'     => __('game::games.line', [
                    'symbol'   => __('game::games.symbols.' . self::NAMES[$symbol]),
                    'position' => __('game::games.positions.' . $position),
                ]),
            ];

            $sum += self::PAYOUTS[$symbol][$position];
        }

        return [$results, $sum];
    }
}
