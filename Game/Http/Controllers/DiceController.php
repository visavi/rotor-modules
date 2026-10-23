<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiceController extends Controller
{
    /**
     * Ставка за бросок и выплата за победу вместе с возвратом ставки
     */
    public const BET = 5;
    public const WIN = 10;

    /**
     * Как часто кубик игрока честный
     *
     * В остальных бросках шестёрка ему не выпадает — на этом держится
     * перевес заведения. С честными кубиками ставка 5 и выплата 10
     * дали бы ровно нулевой исход, а один укороченный бросок из пяти
     * оставляет заведению около 5%
     */
    public const FAIR_ROLLS = 5;

    /**
     * Граней у кубика
     */
    public const FACES = 6;

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
            $this->user = auth()->user() ?? abort(403);

            return $next($request);
        });
    }

    /**
     * Кости
     */
    public function index(): View
    {
        return view('game::dices/index', [
            'user' => $this->user,
            'game' => null,
        ]);
    }

    /**
     * Бросок
     */
    public function roll(Request $request): View|JsonResponse
    {
        $before = $this->user->money;

        if (! $this->user->chargeMoney(self::BET)) {
            abort(403, __('game::games.cannot_play'));
        }

        $dices = [
            'user'   => [$this->rollUser(), $this->rollUser()],
            'banker' => [random_int(1, self::FACES), random_int(1, self::FACES)],
        ];

        $scores = [
            'user'   => array_sum($dices['user']),
            'banker' => array_sum($dices['banker']),
        ];

        $result = match (true) {
            $scores['user'] > $scores['banker'] => 'victory',
            $scores['user'] < $scores['banker'] => 'lost',
            default                             => 'draw',
        };

        // Победа возвращает ставку с выигрышем, ничья — одну ставку
        if ($result === 'victory') {
            $this->user->increment('money', self::WIN);
        }

        if ($result === 'draw') {
            $this->user->increment('money', self::BET);
        }

        $game = [
            'before' => $before,
            'dices'  => $dices,
            'scores' => $scores,
            'result' => $result,
        ];

        $data = ['user' => $this->user, 'game' => $game];

        // Бросок приходит ajax-ом: отдаём только стол, страница не перезагружается
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::dices/_table', $data)->render(),
            ]);
        }

        return view('game::dices/index', $data);
    }

    /**
     * Кубик игрока
     *
     * Каждый пятый бросок идёт без шестёрки: этого хватает, чтобы
     * заведение осталось при своих процентах, и при этом игрок выигрывает
     * заметно чаще, чем при прежней раздаче
     */
    private function rollUser(): int
    {
        $fair = random_int(1, self::FAIR_ROLLS) > 1;

        return random_int(1, $fair ? self::FACES : self::FACES - 1);
    }
}
