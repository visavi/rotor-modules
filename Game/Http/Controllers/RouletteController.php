<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouletteController extends Controller
{
    /**
     * Порядок чисел на европейском колесе, по часовой стрелке от зеро
     */
    public const WHEEL = [
        0, 32, 15, 19, 4, 21, 2, 25, 17, 34, 6, 27, 13, 36, 11, 30, 8, 23,
        10, 5, 24, 16, 33, 1, 20, 14, 31, 9, 22, 18, 29, 7, 28, 12, 35, 3, 26,
    ];

    /**
     * Красные числа, остальные (кроме зеро) черные
     */
    public const RED = [1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36];

    /**
     * Типы ставок и множители выплаты вместе с возвратом ставки
     *
     * Доля заведения берется одним зеро: ставка на цвет выигрывает 18 раз
     * из 37, а платит вдвое, отсюда честные 2.7%
     */
    public const BETS = [
        'red'     => 2,
        'black'   => 2,
        'even'    => 2,
        'odd'     => 2,
        'low'     => 2,
        'high'    => 2,
        'dozen1'  => 3,
        'dozen2'  => 3,
        'dozen3'  => 3,
        'column1' => 3,
        'column2' => 3,
        'column3' => 3,
        'number'  => 36,
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
     * Рулетка
     */
    public function index(Request $request): View
    {
        return view('game::roulette/index', $this->data($request->session()->get('roulette')));
    }

    /**
     * Данные для шаблона
     *
     * @return array<string, mixed>
     */
    private function data(?array $spin): array
    {
        $wheel = array_map(
            fn (int $number) => ['number' => $number, 'color' => $this->color($number)],
            self::WHEEL,
        );

        return [
            'user'  => $this->user,
            'bets'  => self::BETS,
            'wheel' => $wheel,
            'spin'  => $spin,
        ];
    }

    /**
     * Спин
     */
    public function spin(Request $request, Validator $validator): View|RedirectResponse|JsonResponse
    {
        $bet = int($request->input('bet'));
        $type = (string) $request->input('type');
        $guess = int($request->input('number'));

        $validator
            ->gt($bet, 0, ['bet' => __('game::games.bj_bet_required')])
            ->gte($this->user->money, $bet, ['bet' => __('game::games.not_enough_money')])
            ->true(isset(self::BETS[$type]), ['type' => __('game::games.roulette_bet_invalid')]);

        if ($type === 'number') {
            $validator->true($guess >= 0 && $guess <= 36, ['number' => __('game::games.roulette_number_invalid')]);
        }

        if (! $validator->isValid()) {
            // Ставка уходит ajax-ом: редирект с withErrors до игрока не дойдёт,
            // ошибка возвращается тем же json, что понимает ajax ядра
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(' ', $validator->getErrors()),
                ]);
            }

            return redirect('games/roulette')
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        $before = $this->user->money;
        $number = random_int(0, 36);
        $win = $this->isWin($type, $number, $guess) ? $bet * self::BETS[$type] : 0;

        $this->user->decrement('money', $bet);

        if ($win) {
            $this->user->increment('money', $win);
        }

        $spin = [
            'before' => $before,
            'number' => $number,
            'color'  => $this->color($number),
            'bet'    => $bet,
            'type'   => $type,
            'guess'  => $guess,
            'win'    => $win,
        ];

        // Спин уходит ajax-ом: колесо крутится на месте, без перезагрузки страницы
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::roulette/_wheel', $this->data($spin))->render(),
            ]);
        }

        return redirect('games/roulette')
            ->withInput()
            ->with('roulette', $spin);
    }

    /**
     * Цвет числа
     */
    public function color(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        return in_array($number, self::RED, true) ? 'red' : 'black';
    }

    /**
     * Выиграла ли ставка на выпавшем числе
     *
     * Зеро забирает все ставки, кроме ставки на само зеро
     */
    private function isWin(string $type, int $number, int $guess): bool
    {
        if ($type === 'number') {
            return $number === $guess;
        }

        if ($number === 0) {
            return false;
        }

        return match ($type) {
            'red'     => in_array($number, self::RED, true),
            'black'   => ! in_array($number, self::RED, true),
            'even'    => $number % 2 === 0,
            'odd'     => $number % 2 === 1,
            'low'     => $number <= 18,
            'high'    => $number >= 19,
            'dozen1'  => $number <= 12,
            'dozen2'  => $number >= 13 && $number <= 24,
            'dozen3'  => $number >= 25,
            'column1' => $number % 3 === 1,
            'column2' => $number % 3 === 2,
            'column3' => $number % 3 === 0,
            default   => false,
        };
    }
}
