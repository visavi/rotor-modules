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

class MinerController extends Controller
{
    /**
     * Размер поля и допустимое количество мин
     */
    public const CELLS = 25;
    public const MINES = [3, 5, 10];

    /**
     * Доля, которую оставляет себе заведение
     *
     * Множитель считается как «шанс дойти» с этой поправкой, поэтому итог
     * партии не зависит от того, на какой клетке игрок остановился
     */
    private const HOUSE_EDGE = 0.03;

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
     * Минёр
     */
    public function index(Request $request): View|RedirectResponse
    {
        $miner = $request->session()->get('miner');

        // Доигранная партия остаётся до возврата на стартовую страницу,
        // чтобы игрок увидел раскрытое поле
        if ($miner && $miner['status'] !== null) {
            $request->session()->forget('miner');
            $miner = null;
        }

        if ($miner) {
            return redirect('games/miner/game');
        }

        return view('game::miner/index', [
            'user'    => $this->user,
            'mines'   => self::MINES,
            'rewards' => $this->rewards(),
        ]);
    }

    /**
     * Ставка
     */
    public function bet(Request $request, Validator $validator): RedirectResponse|JsonResponse
    {
        $bet = int($request->input('bet'));
        $mines = int($request->input('mines'));

        $current = $request->session()->get('miner');

        // Доигранную партию повтор ставки просто сменяет
        if ($current && $current['status'] === null) {
            return redirect('games/miner/game');
        }

        $request->session()->forget('miner');

        $validator
            ->gt($bet, 0, ['bet' => __('game::games.bj_bet_required')])
            ->gte($this->user->money, $bet, ['bet' => __('game::games.not_enough_money')])
            ->true(in_array($mines, self::MINES, true), ['mines' => __('game::games.miner_mines_invalid')]);

        if (! $validator->isValid()) {
            // Ставка уходит ajax-ом: редирект с withErrors до игрока не дойдёт,
            // ошибка возвращается тем же json, что понимает ajax ядра
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(' ', $validator->getErrors()),
                ]);
            }

            return redirect('games/miner')
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        $this->user->decrement('money', $bet);

        $miner = [
            'bet'    => $bet,
            'mines'  => $mines,
            'field'  => (array) array_rand(array_fill(0, self::CELLS, true), $mines),
            'opened' => [],
            'status' => null,
        ];

        $request->session()->put('miner', $miner);

        // Повтор ставки с доигранного поля тоже уходит ajax-ом
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::miner/_field', $this->data($miner))->render(),
            ]);
        }

        return redirect('games/miner/game')
            ->with('success', __('game::games.bj_bet_made'));
    }

    /**
     * Поле
     */
    public function game(Request $request): View|RedirectResponse
    {
        $miner = $request->session()->get('miner');

        if (! $miner) {
            return redirect('games/miner')
                ->with('danger', __('game::games.bj_bet_needed'));
        }

        return view('game::miner/game', $this->data($miner));
    }

    /**
     * Данные для шаблона
     *
     * @return array<string, mixed>
     */
    private function data(array $miner, ?int $fresh = null, ?int $before = null): array
    {
        return [
            'user'   => $this->user,
            'miner'  => $miner,
            'cells'  => self::CELLS,
            'reward' => $this->reward($miner),
            'next'   => $this->reward($miner, 1),
            'fresh'  => $fresh,
            'before' => $before ?? $this->user->money,
        ];
    }

    /**
     * Ответ хода: ajax подменяет только поле, иначе прежний редирект
     */
    private function respond(Request $request, array $miner, ?int $fresh = null, ?int $before = null): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::miner/_field', $this->data($miner, $fresh, $before))->render(),
            ]);
        }

        return redirect('games/miner/game');
    }

    /**
     * Открытие клетки
     */
    public function go(Request $request): RedirectResponse|JsonResponse
    {
        $miner = $request->session()->get('miner');
        $cell = int($request->input('cell'));

        if (! $miner || $miner['status'] !== null) {
            return redirect('games/miner');
        }

        if ($cell < 0 || $cell >= self::CELLS || in_array($cell, $miner['opened'], true)) {
            return redirect('games/miner/game');
        }

        $before = $this->user->money;

        if (in_array($cell, $miner['field'], true)) {
            $miner['status'] = 'lost';
            $miner['opened'][] = $cell;
            $request->session()->put('miner', $miner);

            return $this->respond($request, $miner, $cell, $before);
        }

        $miner['opened'][] = $cell;

        // Открыты все безопасные клетки — забирать больше нечего
        if (count($miner['opened']) === self::CELLS - $miner['mines']) {
            $this->user->increment('money', $this->reward($miner));
            $miner['status'] = 'won';
        }

        $request->session()->put('miner', $miner);

        return $this->respond($request, $miner, $cell, $before);
    }

    /**
     * Забрать выигрыш
     */
    public function cash(Request $request): RedirectResponse|JsonResponse
    {
        $miner = $request->session()->get('miner');

        if (! $miner || $miner['status'] !== null || ! $miner['opened']) {
            return redirect('games/miner/game');
        }

        $before = $this->user->money;
        $this->user->increment('money', $this->reward($miner));

        $miner['status'] = 'won';
        $request->session()->put('miner', $miner);

        return $this->respond($request, $miner, null, $before);
    }

    /**
     * Считает выплату за открытые клетки
     */
    private function reward(array $miner, int $ahead = 0): int
    {
        $opened = count($miner['opened']) + $ahead;

        if ($opened > self::CELLS - $miner['mines']) {
            return 0;
        }

        return (int) ($miner['bet'] * $this->multiplier($opened, $miner['mines']));
    }

    /**
     * Множитель: обратный шанс дойти до этого хода, за вычетом доли заведения
     *
     * Значение округляется до ближайших пяти сотых (у крупных — до целого),
     * чтобы выплаты выглядели человечно: 145 вместо 144, 170 вместо 167.
     * Округление именно к ближайшему: вверх дало бы шаги, выгодные игроку,
     * вниз — провалы до минус десяти процентов
     */
    private function multiplier(int $opened, int $mines): float
    {
        if ($opened === 0) {
            return 0.0;
        }

        $safe = self::CELLS - $mines;
        $chance = 1.0;

        for ($step = 0; $step < $opened; $step++) {
            $chance *= ($safe - $step) / (self::CELLS - $step);
        }

        $multiplier = (1 - self::HOUSE_EDGE) / $chance;
        $grid = $multiplier < 10 ? 0.05 : 1.0;
        $rounded = round($multiplier / $grid) * $grid;

        // Округление вверх местами перекрывало долю заведения и делало
        // отдельные шаги выгодными игроку — такие опускаем на деление вниз
        if ($rounded * $chance > 1) {
            $rounded -= $grid;
        }

        return $rounded;
    }

    /**
     * Таблица выплат для стартовой страницы: со ставки в сто монет
     */
    private function rewards(): array
    {
        $rewards = [];

        foreach (self::MINES as $mines) {
            foreach ([1, 3, 5, 8, 10] as $opened) {
                $rewards[$mines][$opened] = (int) (100 * $this->multiplier($opened, $mines));
            }
        }

        return $rewards;
    }
}
