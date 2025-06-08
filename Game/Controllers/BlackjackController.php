<?php

declare(strict_types=1);

namespace Modules\Game\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use App\Models\User;
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

        $validator->equal($request->input('_token'), csrf_token(), __('validator.token'))
            ->gt($bet, 0, ['bet' => 'Вы не указали ставку!'])
            ->gte($this->user->money, $bet, ['bet' => 'У вас недостаточно денег для игры!']);

        if ($validator->isValid()) {
            $request->session()->put('blackjack.bet', $bet);

            $this->user->decrement('money', $bet);

            setFlash('success', 'Ставка сделана!');

            return redirect('games/blackjack/game?rand=' . mt_rand(1000, 9999));
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
        $case = $request->input('case');

        $results = [
            'victory' => '<span class="text-success">Вы выиграли</span>',
            'lost'    => '<span class="text-danger">Вы проиграли</span>',
            'draw'    => 'Ничья',
        ];

        if ($request->session()->missing('blackjack.bet')) {
            setFlash('danger', 'Необходимо сделать ставку!');

            return redirect('games/blackjack');
        }

        $scores = $this->takeCard($case);

        $text = false;
        $result = false;

        if ($case === 'end') {
            if ($scores['user'] > $scores['banker']) {
                $result = $results['victory'];
            }
            if ($scores['user'] < $scores['banker']) {
                $result = $results['lost'];
            }
            if ($scores['user'] === $scores['banker']) {
                $result = $results['draw'];
            }
            if ($scores['banker'] > 21) {
                $result = $results['victory'];
            }
        }

        if ($scores['user'] > 21 && $scores['userCards'] !== 2) {
            $text = 'У вас перебор!';
            $result = $results['lost'];
        }
        if ($scores['user'] === 22 && $scores['userCards'] === 2) {
            $text = 'У вас 2 туза!';
            $result = $results['victory'];
        }
        if ($scores['banker'] === 22 && $scores['bankerCards'] === 2) {
            $text = 'У банкира 2 туза!';
            $result = $results['lost'];
        }
        if ($scores['user'] === 21) {
            $text = 'У вас очко!';
            $result = $results['victory'];
        }
        if ($scores['banker'] === 21) {
            $text = 'У банкира очко!';
            $result = $results['lost'];
        }
        if (($scores['user'] === 21 && $scores['banker'] === 21) || ($scores['user'] === 22 && $scores['banker'] === 22)) {
            $result = $results['draw'];
        }

        $blackjack = $request->session()->get('blackjack');

        if ($result) {
            if ($result === $results['victory']) {
                $this->user->increment('money', $blackjack['bet'] * 2);
            } elseif ($result === $results['draw']) {
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
    private function takeCard(?string $case): array
    {
        $rand = mt_rand(16, 18);

        if (session()->missing('blackjack.deck')) {
            session()->put('blackjack.deck', array_combine(range(1, 52), range(1, 52)));
        }

        if (session()->missing('blackjack.cards')) {
            session()->put('blackjack.cards', []);
            $case = 'take';
        }

        if (session()->missing('blackjack.bankercards')) {
            session()->put('blackjack.bankercards', []);
        }

        if ($case === 'take') {
            $card = array_rand(session()->get('blackjack.deck'));
            session()->push('blackjack.cards', $card);
            session()->forget('blackjack.deck.' . $card);

            if ($this->cardsScore(session()->get('blackjack.bankercards')) < $rand) {
                $card2 = array_rand(session()->get('blackjack.deck'));
                session()->push('blackjack.bankercards', $card2);
                session()->forget('blackjack.deck.' . $card2);
            }
        }

        if ($case === 'end') {
            while ($this->cardsScore(session()->get('blackjack.bankercards')) < $rand) {
                $card2 = array_rand(session()->get('blackjack.deck'));
                session()->push('blackjack.bankercards', $card2);
                session()->forget('blackjack.deck.' . $card2);
            }
        }

        return [
            'user'        => $this->cardsScore(session()->get('blackjack.cards')),
            'userCards'   => count(session()->get('blackjack.cards')),
            'banker'      => $this->cardsScore(session()->get('blackjack.bankercards')),
            'bankerCards' => count(session()->get('blackjack.bankercards')),
        ];
    }
}
