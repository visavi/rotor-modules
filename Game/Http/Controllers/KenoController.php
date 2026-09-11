<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Game\Http\Concerns\RejectsInvalidInput;
use App\Models\User;
use App\Support\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KenoController extends Controller
{
    use RejectsInvalidInput;

    /**
     * Размер поля
     */
    public const FIELD = 40;

    /**
     * Сколько чисел вытягивает автомат
     */
    public const DRAW = 10;

    /**
     * Сколько чисел отмечает игрок
     *
     * Число закреплено: пока его выбирал игрок, у каждого количества была
     * своя таблица выплат, и девять таблиц приходилось сравнивать между собой
     */
    public const PICKS = 6;

    /**
     * Выплаты: сколько угадано => множитель к ставке
     *
     * Возврат по таблице около 95%. Вероятность ровно k совпадений считается
     * гипергеометрическим распределением, точный возврат проверяет тест,
     * поэтому править множители на глаз нельзя.
     *
     * Нижняя ступень платит больше ставки, а число отметок выбрано так,
     * чтобы выигрыш случался в 47% партий против 53% проигрышей. Игрок
     * теряет деньги медленно и часто уходит в плюс, а редкий куш за полное
     * совпадение держится на джекпоте, не влияя на эту частоту
     */
    public const PAYOUTS = [
        2 => 1.5,
        3 => 2,
        4 => 5,
        5 => 20,
        6 => 1000,
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
     * Кено
     */
    public function index(Request $request): View
    {
        return view('game::keno/index', $this->data($request->session()->get('keno')));
    }

    /**
     * Данные для шаблона
     *
     * @return array<string, mixed>
     */
    private function data(?array $game): array
    {
        return [
            'user'   => $this->user,
            'game'   => $game,
            'limits' => $this->limits(),
        ];
    }

    /**
     * Правила игры
     */
    public function rules(): View
    {
        return view('game::keno/rules', [
            'payouts' => self::PAYOUTS,
            'limits'  => $this->limits(),
        ]);
    }

    /**
     * Размеры поля для шаблонов
     *
     * @return array<string, int>
     */
    private function limits(): array
    {
        return [
            'field' => self::FIELD,
            'draw'  => self::DRAW,
            'picks' => self::PICKS,
        ];
    }

    /**
     * Тираж
     */
    public function play(Request $request, Validator $validator): View|RedirectResponse|JsonResponse
    {
        $bet = int($request->input('bet'));
        $picks = $this->picks($request);

        $validator
            ->gt($bet, 0, ['bet' => __('game::games.bj_bet_required')])
            ->gte($this->user->money, $bet, ['bet' => __('game::games.not_enough_money')])
            ->true(count($picks) === self::PICKS, ['numbers' => __('game::games.keno_picks_invalid', [
                'picks' => self::PICKS,
            ])]);

        if (! $validator->isValid()) {
            if ($answer = $this->ajaxError($request, $validator)) {
                return $answer;
            }

            return redirect('games/keno')
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        $before = $this->user->money;
        $drawn = $this->draw();
        $matched = array_values(array_intersect($picks, $drawn));
        $win = $this->payout($bet, count($matched));

        $this->user->decrement('money', $bet);

        if ($win) {
            $this->user->increment('money', $win);
        }

        $game = [
            'before'  => $before,
            'bet'     => $bet,
            'picks'   => $picks,
            'drawn'   => $drawn,
            'matched' => $matched,
            'win'     => $win,
        ];

        // Тираж уходит ajax-ом: страница не перезагружается, шары не гаснут на релоаде
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::keno/_board', $this->data($game))->render(),
            ]);
        }

        return redirect('games/keno')
            ->withInput()
            ->with('keno', $game);
    }

    /**
     * Отмеченные игроком числа
     *
     * Повторы и числа вне поля отбрасываются: иначе одним числом,
     * присланным дважды, можно было бы удвоить шанс совпадения
     */
    private function picks(Request $request): array
    {
        $numbers = (array) $request->input('numbers', []);

        $picks = [];
        foreach ($numbers as $number) {
            // Приводим сами: int() берёт модуль, и минус первого числа стал бы отметкой
            $number = (int) $number;

            if ($number >= 1 && $number <= self::FIELD) {
                $picks[$number] = $number;
            }
        }

        sort($picks);

        return $picks;
    }

    /**
     * Тираж автомата: двадцать разных чисел поля
     */
    private function draw(): array
    {
        $field = range(1, self::FIELD);
        $drawn = [];

        for ($i = 0; $i < self::DRAW; $i++) {
            $index = random_int(0, count($field) - 1);
            $drawn[] = $field[$index];
            array_splice($field, $index, 1);
        }

        return $drawn;
    }

    /**
     * Доля партий, в которых автомат хоть что-то платит
     */
    public function frequency(): float
    {
        $chance = 0.0;

        foreach (array_keys(self::PAYOUTS) as $matched) {
            $chance += $this->chance($matched);
        }

        return $chance;
    }

    /**
     * Вероятность ровно $matched совпадений
     *
     * Гипергеометрическое распределение: из поля тянут DRAW чисел без
     * возврата, поэтому биномиальное здесь дало бы завышенный хвост
     */
    public function chance(int $matched): float
    {
        return $this->binomial(self::PICKS, $matched)
            * $this->binomial(self::FIELD - self::PICKS, self::DRAW - $matched)
            / $this->binomial(self::FIELD, self::DRAW);
    }

    /**
     * Число сочетаний из $n по $k
     */
    private function binomial(int $n, int $k): float
    {
        if ($k < 0 || $k > $n) {
            return 0.0;
        }

        $result = 1.0;

        for ($i = 1; $i <= $k; $i++) {
            $result = $result * ($n - $k + $i) / $i;
        }

        return $result;
    }

    /**
     * Выплата по числу совпадений
     */
    private function payout(int $bet, int $matched): int
    {
        $multiplier = self::PAYOUTS[$matched] ?? 0;

        return (int) ($bet * $multiplier);
    }
}
