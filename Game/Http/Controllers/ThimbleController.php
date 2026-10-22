<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ThimbleController extends Controller
{
    /**
     * Ставка и выплата за найденный шарик
     */
    public const BET = 5;
    public const WIN = 10;

    /**
     * Число напёрстков
     */
    public const THIMBLES = 3;

    /**
     * Шанс победы в тысячных долях
     *
     * Напёрсточник кладёт шарик после выбора игрока, поэтому шанс задаётся
     * прямо, а не числом напёрстков: честная треть оставляла игрока в минусе
     * две партии из трёх, а здесь он выигрывает почти так же часто, как
     * проигрывает, и при выплате вдвое теряет около 5% ставки
     */
    public const WIN_CHANCE = 475;

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
     * Напёрстки
     */
    public function index(): View
    {
        return view('game::thimbles/index', $this->data());
    }

    /**
     * Игра в напёрстки
     */
    public function go(Request $request): View|RedirectResponse|JsonResponse
    {
        $request->validate([
            'thimble' => ['required', 'integer', 'between:1,' . self::THIMBLES],
        ], [
            'thimble.*' => __('game::games.thimbles_not_chosen') . ' ' . __('validator.between', ['min' => 1, 'max' => self::THIMBLES]),
        ]);

        $thimble = $request->integer('thimble');

        $before = $this->user->money;

        if (! $this->user->chargeMoney(self::BET)) {
            throw ValidationException::withMessages(['thimble' => __('game::games.not_enough_money')]);
        }

        $ball = $this->ball($thimble);
        $won = $thimble === $ball;

        if ($won) {
            $this->user->increment('money', self::WIN);
        }

        $game = [
            'before'  => $before,
            'thimble' => $thimble,
            'ball'    => $ball,
            'result'  => $won ? 'won' : 'lost',
        ];

        $data = $this->data($game);

        // Ход уходит ajax-ом: страница не перезагружается, анимация не рвётся
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::thimbles/_table', $data)->render(),
            ]);
        }

        return view('game::thimbles/index', $data);
    }

    /**
     * Под каким напёрстком оказался шарик
     *
     * Шарик кладётся уже после выбора: сначала решается, выиграл ли игрок,
     * и только потом шарику ищется место
     */
    private function ball(int $thimble): int
    {
        if (random_int(1, 1000) <= self::WIN_CHANCE) {
            return $thimble;
        }

        $others = array_values(array_diff(range(1, self::THIMBLES), [$thimble]));

        return $others[random_int(0, count($others) - 1)];
    }

    /**
     * Данные для шаблона
     *
     * @return array<string, mixed>
     */
    private function data(?array $game = null): array
    {
        return [
            'user' => $this->user,
            'game' => $game,
            'bet'  => self::BET,
            'win'  => self::WIN,
        ];
    }
}
