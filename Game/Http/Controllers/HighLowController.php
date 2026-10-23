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
use Modules\Game\Http\Controllers\Concerns\HandlesBets;

class HighLowController extends Controller
{
    use HandlesBets;

    /**
     * Доля заведения: каждый шаг оплачивается на 5% дешевле честной цены
     */
    public const RETURN_RATE = 0.95;

    /**
     * Сколько карт в колоде
     */
    public const DECK = 52;

    /**
     * Больше-меньше
     */
    public function index(Request $request, #[CurrentUser] User $user): View
    {
        return view('game::highlow/index', $this->data($user, $request));
    }

    /**
     * Правила игры
     */
    public function rules(): View
    {
        return view('game::highlow/rules');
    }

    /**
     * Ставка
     */
    public function bet(Request $request, #[CurrentUser] User $user): RedirectResponse|JsonResponse
    {
        if ($request->session()->has('highlow')) {
            return redirect('games/highlow');
        }

        $request->validate(['bet' => $this->betRules($user)], $this->betMessages());

        $bet = $request->integer('bet');

        $before = $user->money;

        if (! $user->chargeMoney($bet)) {
            throw ValidationException::withMessages(['bet' => __('game::games.not_enough_money')]);
        }

        $deck = range(1, self::DECK);
        $card = $this->pull($deck);

        $request->session()->put('highlow', [
            'bet'        => $bet,
            'before'     => $before,
            'deck'       => $deck,
            'cards'      => [$card],
            'multiplier' => 1.0,
            'shown'      => 0,
        ]);

        return redirect('games/highlow');
    }

    /**
     * Ход: ставка на старшую или младшую карту
     */
    public function move(Request $request, #[CurrentUser] User $user): RedirectResponse|JsonResponse
    {
        $game = $request->session()->get('highlow');
        $guess = $request->input('guess');

        if (! $game || ! in_array($guess, ['higher', 'lower'], true)) {
            return $this->back($user, $request);
        }

        $current = $this->rank(end($game['cards']));
        $chances = $this->chances($game['deck'], $current);

        // Ставку без шансов не принимаем: на тузе некуда идти выше
        if (! $chances[$guess]) {
            return $this->back($user, $request);
        }

        $game['shown'] = count($game['cards']);
        $card = $this->pull($game['deck']);
        $game['cards'][] = $card;

        $rank = $this->rank($card);

        if ($rank === $current) {
            // Равные достоинства — ничья: множитель и ставка остаются как были
            $game['draw'] = true;
            $request->session()->put('highlow', $game);

            return $this->back($user, $request);
        }

        $won = $guess === 'higher' ? $rank > $current : $rank < $current;

        if (! $won) {
            $game['result'] = 'lost';
            $game['win'] = 0;

            return $this->finish($user, $request, $game);
        }

        unset($game['draw']);
        $game['multiplier'] = round($game['multiplier'] * $this->step($chances, $guess), 4);

        // Колода кончилась — забираем выигрыш, ходить больше нечем
        if (! $game['deck']) {
            return $this->cashOut($user, $request, $game);
        }

        $request->session()->put('highlow', $game);

        return $this->back($user, $request);
    }

    /**
     * Забрать выигрыш
     */
    public function cash(Request $request, #[CurrentUser] User $user): RedirectResponse|JsonResponse
    {
        $game = $request->session()->get('highlow');

        // Забирать нечего, пока не угадана хотя бы одна карта: иначе это
        // просто возврат ставки, названный выигрышем
        if (! $game || $game['multiplier'] <= 1.0) {
            return $this->back($user, $request);
        }

        return $this->cashOut($user, $request, $game);
    }

    /**
     * Начисляет выигрыш и заканчивает партию
     */
    private function cashOut(User $user, Request $request, array $game): RedirectResponse|JsonResponse
    {
        $win = (int) ($game['bet'] * $game['multiplier']);

        $user->increment('money', $win);

        $game['result'] = 'victory';
        $game['win'] = $win;

        return $this->finish($user, $request, $game);
    }

    /**
     * Убирает партию из сессии и показывает итог
     */
    private function finish(User $user, Request $request, array $game): RedirectResponse|JsonResponse
    {
        $request->session()->forget('highlow');
        $request->session()->flash('highlow_result', $game);

        return $this->back($user, $request, $game);
    }

    /**
     * Ответ на ход: ajax получает только стол, обычный переход — редирект
     */
    private function back(User $user, Request $request, ?array $game = null): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::highlow/_table', $this->data($user, $request, $game))->render(),
            ]);
        }

        return redirect('games/highlow');
    }

    /**
     * Данные стола
     */
    private function data(User $user, Request $request, ?array $game = null): array
    {
        $game ??= $request->session()->get('highlow') ?? $request->session()->get('highlow_result');

        $chances = null;

        if ($game && ! isset($game['result'])) {
            $chances = $this->chances($game['deck'], $this->rank(end($game['cards'])));
            $chances['higher_payout'] = $chances['higher'] ? round($game['multiplier'] * $this->step($chances, 'higher'), 2) : null;
            $chances['lower_payout'] = $chances['lower'] ? round($game['multiplier'] * $this->step($chances, 'lower'), 2) : null;
        }

        return [
            'user'    => $user,
            'game'    => $game,
            'chances' => $chances,
        ];
    }

    /**
     * Тянет случайную карту из колоды
     */
    private function pull(array &$deck): int
    {
        $index = random_int(0, count($deck) - 1);
        $card = $deck[$index];

        array_splice($deck, $index, 1);

        return $card;
    }

    /**
     * Достоинство карты: 0 — двойка, 12 — туз
     */
    private function rank(int $card): int
    {
        return intdiv($card - 1, 4);
    }

    /**
     * Сколько карт в колоде старше, младше и равны текущей
     */
    private function chances(array $deck, int $current): array
    {
        $chances = ['higher' => 0, 'lower' => 0, 'equal' => 0];

        foreach ($deck as $card) {
            $rank = $this->rank($card);

            if ($rank > $current) {
                $chances['higher']++;
            } elseif ($rank < $current) {
                $chances['lower']++;
            } else {
                $chances['equal']++;
            }
        }

        return $chances;
    }

    /**
     * Цена шага
     *
     * Равные достоинства не выигрывают и не проигрывают, поэтому в цене
     * они не участвуют: множитель считается по шансам среди тех карт,
     * которые исход решают
     */
    private function step(array $chances, string $guess): float
    {
        $decisive = $chances['higher'] + $chances['lower'];

        return self::RETURN_RATE * $decisive / $chances[$guess];
    }
}
