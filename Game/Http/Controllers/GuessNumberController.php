<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GuessNumberController extends Controller
{
    /**
     * Цена попытки
     */
    public const PRICE = 15;

    /**
     * Награда за угаданное число
     *
     * Приз фиксированный: он не зависит от того, с какой попытки число
     * угадано, поэтому игроку незачем экономить ходы ради множителя
     */
    public const PRIZE = 250;

    /**
     * Границы диапазона и число попыток
     *
     * Пятью делениями пополам из сотни отсекается 31 число, поэтому даже
     * безошибочная игра выигрывает лишь в трети партий
     */
    public const MIN = 1;
    public const MAX = 100;
    public const TRIES = 5;

    /**
     * Угадай число
     */
    public function index(Request $request, #[CurrentUser] User $user): View
    {
        return view('game::guess/index', $this->data($user, $request));
    }

    /**
     * Попытка
     */
    public function go(Request $request, #[CurrentUser] User $user): View|RedirectResponse|JsonResponse
    {
        $request->validate([
            'guess' => ['required', 'integer', 'between:' . self::MIN . ',' . self::MAX],
        ], [
            'guess.*' => __('game::games.guess_number_required') . ' ' . __('validator.between', ['min' => self::MIN, 'max' => self::MAX]),
        ]);

        $number = $request->integer('guess');

        $before = $user->money;

        // Списание до новой партии: без денег она не должна начаться
        if (! $user->chargeMoney(self::PRICE)) {
            throw ValidationException::withMessages(['guess' => __('game::games.not_enough_money')]);
        }

        if ($request->session()->missing('guess.number')) {
            $request->session()->put('guess', [
                'number'  => random_int(self::MIN, self::MAX),
                'try'     => self::TRIES,
                'history' => [],
            ]);
        }

        $guess = $request->session()->get('guess');

        $guess['try']--;

        $hint = match (true) {
            $number === $guess['number'] => 'exact',
            $number > $guess['number']   => 'less',
            default                      => 'more',
        };

        $guess['history'][] = ['number' => $number, 'hint' => $hint];
        $won = $hint === 'exact';
        $prize = $won ? self::PRIZE : 0;

        if ($prize) {
            $user->increment('money', $prize);
        }

        // Партия заканчивается угадыванием или последней попыткой
        if ($won || $guess['try'] < 1) {
            $request->session()->forget('guess');
        } else {
            $request->session()->put('guess', $guess);
        }

        $game = [
            'before'  => $before,
            'number'  => $won || $guess['try'] < 1 ? $guess['number'] : null,
            'try'     => $guess['try'],
            'history' => $guess['history'],
            'prize'   => $prize,
            'result'  => $won ? 'won' : ($guess['try'] < 1 ? 'lost' : null),
        ];

        $data = $this->data($user, $request, $game);

        // Попытка уходит ajax-ом: страница не перезагружается, анимация не рвётся
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::guess/_game', $data)->render(),
            ]);
        }

        return view('game::guess/index', $data);
    }

    /**
     * Сброс незаконченной партии
     */
    public function reset(Request $request, #[CurrentUser] User $user): RedirectResponse|JsonResponse
    {
        $request->session()->forget('guess');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::guess/_game', $this->data($user, $request))->render(),
            ]);
        }

        return redirect('games/guess');
    }

    /**
     * Данные для шаблона
     *
     * @return array<string, mixed>
     */
    private function data(User $user, Request $request, ?array $game = null): array
    {
        $guess = $request->session()->get('guess');

        // Загаданное число незаконченной партии в шаблон не уходит
        $game ??= $guess ? [
            'before'  => $user->money,
            'number'  => null,
            'try'     => $guess['try'],
            'history' => $guess['history'],
            'prize'   => 0,
            'result'  => null,
        ] : null;

        return [
            'user'   => $user,
            'game'   => $game,
            'price'  => self::PRICE,
            'reward' => self::PRIZE,
        ];
    }
}
