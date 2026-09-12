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

class BaccaratController extends Controller
{
    /**
     * Типы ставок и множители выплаты вместе с возвратом ставки
     *
     * Банкир выигрывает чаще игрока, поэтому его выплата урезана
     * комиссией в 5%. При ничьей ставки на игрока и банкира возвращаются
     */
    public const BETS = [
        'player' => 2.0,
        'banker' => 1.95,
        'tie'    => 9.0,
    ];

    /**
     * Рука, с которой карты больше не берут
     */
    private const NATURAL = 8;

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
     * Баккара
     */
    public function index(Request $request): View
    {
        return view('game::baccarat/index', [
            'user' => $this->user,
            'bets' => self::BETS,
            // Незаконченная партия ждёт решения игрока, доигранная приходит флешем
            'game' => $this->current($request),
        ]);
    }

    /**
     * Раздача
     */
    public function deal(Request $request, Validator $validator): RedirectResponse|JsonResponse
    {
        $bet = int($request->input('bet'));
        $type = (string) $request->input('type');

        $validator
            ->gt($bet, 0, ['bet' => __('game::games.bj_bet_required')])
            ->gte($this->user->money, $bet, ['bet' => __('game::games.not_enough_money')])
            ->true(isset(self::BETS[$type]), ['type' => __('game::games.baccarat_bet_invalid')]);

        if (! $validator->isValid()) {
            // Ставка уходит ajax-ом: редирект с withErrors до игрока не дойдёт,
            // ошибка возвращается тем же json, что понимает ajax ядра
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(' ', $validator->getErrors()),
                ]);
            }

            return redirect('games/baccarat')
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        $before = $this->user->money;
        $this->user->decrement('money', $bet);

        $deck = array_combine(range(1, 52), range(1, 52));

        $player = [$this->draw($deck), $this->draw($deck)];
        $banker = [$this->draw($deck), $this->draw($deck)];

        $game = compact('deck', 'player', 'banker') + compact('before', 'bet', 'type');

        // На ровно пяти очках игрок решает сам — правило chemin de fer
        if ($this->needsDecision($player, $banker)) {
            $request->session()->put('baccarat', $game);

            return $this->back($request, url('games/baccarat'), $game + ['player_total' => $this->total($player)]);
        }

        return $this->finish($request, $game, $this->total($player) <= 5);
    }

    /**
     * Партия на экране: ожидающая решения или только что доигранная
     */
    private function current(Request $request): ?array
    {
        $pending = $request->session()->get('baccarat');

        if ($pending) {
            return $pending + ['player_total' => $this->total($pending['player'])];
        }

        return $request->session()->get('baccarat_result');
    }

    /**
     * Решение игрока на пяти очках: взять третью карту или остановиться
     */
    public function decide(Request $request): RedirectResponse|JsonResponse
    {
        $game = $request->session()->get('baccarat');

        if (! $game || ! $this->needsDecision($game['player'], $game['banker'])) {
            return $this->back($request, url('games/baccarat'));
        }

        // Карты, которые игрок уже видел, вьюха не анимирует заново
        return $this->finish($request, $game, $request->input('draw') === '1', [
            'player' => count($game['player']),
            'banker' => count($game['banker']),
        ]);
    }

    /**
     * Стоит ли спрашивать игрока: натурал и любая сумма кроме пяти решают за него
     */
    private function needsDecision(array $player, array $banker): bool
    {
        // Пятёрка сама по себе меньше натурала, проверять остаётся только банкира
        return $this->total($player) === 5 && $this->total($banker) < self::NATURAL;
    }

    /**
     * Дораздает партию и рассчитывает ставку
     */
    private function finish(Request $request, array $game, bool $playerDraws, ?array $shown = null): RedirectResponse|JsonResponse
    {
        $hands = $this->play($game['deck'], $game['player'], $game['banker'], $playerDraws);
        $result = $this->result($hands['player_total'], $hands['banker_total']);
        $win = $this->payout($game['type'], $result, $game['bet']);

        if ($win) {
            $this->user->increment('money', $win);
        }

        $request->session()->forget('baccarat');

        return $this->back($request, url('games/baccarat'), $hands + [
            'shown'  => $shown ?? ['player' => 0, 'banker' => 0],
            'before' => $game['before'],
            'bet'    => $game['bet'],
            'type'   => $game['type'],
            'result' => $result,
            'win'    => $win,
        ]);
    }

    /**
     * Возврат на страницу игры: ajax получает готовый стол, обычный запрос — редирект
     */
    private function back(Request $request, string $url, ?array $game = null): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::baccarat/_table', [
                    'user' => $this->user,
                    'game' => $game,
                ])->render(),
            ]);
        }

        return redirect($url)->withInput()->with('baccarat_result', $game);
    }

    /**
     * Дораздает партию: третья карта игрока, затем банкир по таблице
     *
     * На пяти очках решение приходит от игрока, на остальных суммах
     * $playerDraws уже посчитан по правилам
     */
    private function play(array $deck, array $player, array $banker, bool $playerDraws): array
    {
        $playerTotal = $this->total($player);
        $bankerTotal = $this->total($banker);

        // Натурал останавливает раздачу на обеих руках
        if ($playerTotal < self::NATURAL && $bankerTotal < self::NATURAL) {
            $playerThird = null;

            if ($playerDraws) {
                $card = $this->draw($deck);
                $player[] = $card;
                $playerThird = $this->cardValue($card);
                $playerTotal = $this->total($player);
            }

            if ($this->bankerDraws($bankerTotal, $playerThird)) {
                $banker[] = $this->draw($deck);
                $bankerTotal = $this->total($banker);
            }
        }

        return [
            'player'       => $player,
            'banker'       => $banker,
            'player_total' => $playerTotal,
            'banker_total' => $bankerTotal,
        ];
    }

    /**
     * Берет карту из колоды
     */
    private function draw(array &$deck): int
    {
        $card = array_rand($deck);
        unset($deck[$card]);

        return (int) $card;
    }

    /**
     * Очки карты: фигуры и десятки не считаются, туз дает одно очко
     */
    private function cardValue(int $card): int
    {
        $rank = intdiv($card - 1, 4);

        return match (true) {
            $rank <= 7 => $rank + 2,
            $rank === 12 => 1,
            default    => 0,
        };
    }

    /**
     * Очки руки считаются по модулю десяти
     */
    private function total(array $cards): int
    {
        return array_sum(array_map(fn (int $card) => $this->cardValue($card), $cards)) % 10;
    }

    /**
     * Берет ли банкир третью карту
     *
     * Пока игрок не тянул третью карту, банкир играет как игрок,
     * иначе решение зависит от достоинства этой карты
     */
    private function bankerDraws(int $bankerTotal, ?int $playerThird): bool
    {
        if ($playerThird === null) {
            return $bankerTotal <= 5;
        }

        return match ($bankerTotal) {
            0, 1, 2 => true,
            3       => $playerThird !== 8,
            4       => $playerThird >= 2 && $playerThird <= 7,
            5       => $playerThird >= 4 && $playerThird <= 7,
            6       => $playerThird >= 6 && $playerThird <= 7,
            default => false,
        };
    }

    /**
     * Победившая сторона
     */
    private function result(int $playerTotal, int $bankerTotal): string
    {
        return match (true) {
            $playerTotal > $bankerTotal => 'player',
            $playerTotal < $bankerTotal => 'banker',
            default                     => 'tie',
        };
    }

    /**
     * Выплата вместе с возвратом ставки
     *
     * Ничья не трогает ставки на игрока и банкира, комиссия с выигрыша
     * банкира округляется в пользу заведения
     */
    private function payout(string $type, string $result, int $bet): int
    {
        if ($type === $result) {
            return (int) floor($bet * self::BETS[$type]);
        }

        return $result === 'tie' ? $bet : 0;
    }
}
