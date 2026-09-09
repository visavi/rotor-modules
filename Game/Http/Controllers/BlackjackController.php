<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Validator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlackjackController extends Controller
{
    /**
     * Банкир добирает карты, пока не наберёт столько очков
     */
    private const BANKER_STANDS = 17;

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
     * Очко
     */
    public function index(): View
    {
        return view('game::blackjack/index', ['user' => $this->user]);
    }

    /**
     * Ставка
     */
    public function bet(Request $request, Validator $validator): RedirectResponse
    {
        $bet = int($request->input('bet'));

        if ($request->session()->has('blackjack.bet')) {
            return redirect('games/blackjack/game');
        }

        $validator
            ->gt($bet, 0, ['bet' => __('game::games.bj_bet_required')])
            ->gte($this->user->money, $bet, ['bet' => __('game::games.not_enough_money')]);

        if ($validator->isValid()) {
            $request->session()->put('blackjack.bet', $bet);

            $this->user->decrement('money', $bet);

            return redirect('games/blackjack/game')
                ->with('success', __('game::games.bj_bet_made'));
        }

        return redirect('games/blackjack')
            ->withInput()
            ->withErrors($validator->getErrors());
    }

    /**
     * Игра
     */
    public function game(Request $request): View|RedirectResponse
    {
        $input = $request->input('case');
        $case = in_array($input, ['take', 'end'], true) ? $input : null;

        if ($request->session()->missing('blackjack.bet')) {
            return redirect('games/blackjack')
                ->with('danger', __('game::games.bj_bet_needed'));
        }

        $scores = $this->takeCard($request->session(), $case);

        $text = null;
        $result = null;

        if ($case === 'end') {
            $result = match (true) {
                $scores['user'] > $scores['banker'] => 'victory',
                $scores['user'] < $scores['banker'] => 'lost',
                default                             => 'draw',
            };

            if ($scores['banker'] > 21) {
                $result = 'victory';
            }
        }

        if ($scores['user'] > 21 && $scores['userCards'] !== 2) {
            $text = __('game::games.bj_bust');
            $result = 'lost';
        }
        if ($scores['user'] === 22 && $scores['userCards'] === 2) {
            $text = __('game::games.bj_two_aces');
            $result = 'victory';
        }
        if ($scores['user'] === 21) {
            $text = __('game::games.bj_blackjack');
            $result = 'victory';
        }

        // Рука банкира закрыта до вскрытия: иначе он выигрывал на своём доборе,
        // пока игрок ещё решал, брать ли карту
        if ($case === 'end') {
            if ($scores['banker'] === 22 && $scores['bankerCards'] === 2) {
                $text = __('game::games.bj_banker_two_aces');
                $result = 'lost';
            }
            if ($scores['banker'] === 21) {
                $text = __('game::games.bj_banker_blackjack');
                $result = 'lost';
            }
            if (($scores['user'] === 21 && $scores['banker'] === 21) || ($scores['user'] === 22 && $scores['banker'] === 22)) {
                $result = 'draw';
            }
        }

        $blackjack = $request->session()->get('blackjack');

        // Показываем движение по счёту: сколько зачислено за победу или ничью
        // и сколько ушло при проигрыше. Победа забирает весь кон, как в правилах
        $amount = null;

        if ($result !== null) {
            $amount = $blackjack['bet'];

            if ($result === 'victory') {
                $amount = $this->payout($blackjack['bet'], $scores);
                $this->user->increment('money', $amount);
            } elseif ($result === 'draw') {
                $this->user->increment('money', $amount);
            }

            $request->session()->forget('blackjack');
        }

        $user = $this->user;

        return view('game::blackjack/game', compact('user', 'blackjack', 'scores', 'result', 'text', 'amount'));
    }

    /**
     * Правила игры
     */
    public function rules(): View
    {
        return view('game::blackjack/rules');
    }

    /**
     * Считает выплату за победу
     *
     * Очко и два туза оплачиваются как 3:2 — с учётом уже списанной ставки
     * игрок получает две с половиной ставки, обычная победа приносит две
     */
    private function payout(int $bet, array $scores): int
    {
        $isBlackjack = $scores['user'] === 21
            || ($scores['user'] === 22 && $scores['userCards'] === 2);

        return $isBlackjack ? (int) ($bet * 2.5) : $bet * 2;
    }

    /**
     * Подсчитывает очки карт
     */
    private function cardsScore(array $cards): int
    {
        $score = [];

        foreach ($cards as $card) {
            if ($card > 48) {
                $score[] = 11;
                continue;
            }

            if ($card > 36) {
                $score[] = (int) (($card - 1) / 4) - 7;
                continue;
            }

            $score[] = (int) (($card - 1) / 4) + 2;
        }

        return array_sum($score);
    }

    /**
     * Взятие карты
     */
    private function takeCard(Session $session, ?string $case): array
    {
        $isNewGame = $session->missing('blackjack.cards');
        $deck = $session->get('blackjack.deck', array_combine(range(1, 52), range(1, 52)));
        $cards = $session->get('blackjack.cards', []);
        $bankerCards = $session->get('blackjack.bankercards', []);

        if ($isNewGame) {
            $case = 'take';
        }

        if ($case === 'take') {
            $card = array_rand($deck);
            $cards[] = $card;
            unset($deck[$card]);

            if ($this->cardsScore($bankerCards) < self::BANKER_STANDS) {
                $card2 = array_rand($deck);
                $bankerCards[] = $card2;
                unset($deck[$card2]);
            }
        }

        if ($case === 'end') {
            while ($this->cardsScore($bankerCards) < self::BANKER_STANDS) {
                $card2 = array_rand($deck);
                $bankerCards[] = $card2;
                unset($deck[$card2]);
            }
        }

        $session->put('blackjack.deck', $deck);
        $session->put('blackjack.cards', $cards);
        $session->put('blackjack.bankercards', $bankerCards);

        return [
            'user'        => $this->cardsScore($cards),
            'userCards'   => count($cards),
            'banker'      => $this->cardsScore($bankerCards),
            'bankerCards' => count($bankerCards),
        ];
    }
}
