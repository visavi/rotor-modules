<?php

declare(strict_types=1);

namespace Modules\Game\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlackjackController extends Controller
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
            ->gt($bet, 0, ['bet' => 'Вы не указали ставку!'])
            ->gte($this->user->money, $bet, ['bet' => 'У вас недостаточно денег для игры!']);

        if ($validator->isValid()) {
            $request->session()->put('blackjack.bet', $bet);

            $this->user->decrement('money', $bet);

            setFlash('success', 'Ставка сделана!');

            return redirect('games/blackjack/game');
        }

        setInput($request->all());
        setFlash('danger', $validator->getErrors());

        return redirect('games/blackjack');
    }

    /**
     * Игра
     */
    public function game(Request $request): View|RedirectResponse
    {
        $input = $request->input('case');
        $case = in_array($input, ['take', 'end'], true) ? $input : null;

        if ($request->session()->missing('blackjack.bet')) {
            setFlash('danger', 'Необходимо сделать ставку!');

            return redirect('games/blackjack');
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
            $text = 'У вас перебор!';
            $result = 'lost';
        }
        if ($scores['user'] === 22 && $scores['userCards'] === 2) {
            $text = 'У вас 2 туза!';
            $result = 'victory';
        }
        if ($scores['banker'] === 22 && $scores['bankerCards'] === 2) {
            $text = 'У банкира 2 туза!';
            $result = 'lost';
        }
        if ($scores['user'] === 21) {
            $text = 'У вас очко!';
            $result = 'victory';
        }
        if ($scores['banker'] === 21) {
            $text = 'У банкира очко!';
            $result = 'lost';
        }
        if (($scores['user'] === 21 && $scores['banker'] === 21) || ($scores['user'] === 22 && $scores['banker'] === 22)) {
            $result = 'draw';
        }

        $blackjack = $request->session()->get('blackjack');

        if ($result !== null) {
            if ($result === 'victory') {
                $this->user->increment('money', $blackjack['bet'] * 2);
            } elseif ($result === 'draw') {
                $this->user->increment('money', $blackjack['bet']);
            }

            $request->session()->forget('blackjack');
        }

        $user = $this->user;

        return view('game::blackjack/game', compact('user', 'blackjack', 'scores', 'result', 'text'));
    }

    /**
     * Правила игры
     */
    public function rules(): View
    {
        return view('game::blackjack/rules');
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
        $rand = random_int(16, 18);
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

            if ($this->cardsScore($bankerCards) < $rand) {
                $card2 = array_rand($deck);
                $bankerCards[] = $card2;
                unset($deck[$card2]);
            }
        }

        if ($case === 'end') {
            while ($this->cardsScore($bankerCards) < $rand) {
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
