<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KenoController extends Controller
{
    /**
     * Размер поля
     */
    public const FIELD = 80;

    /**
     * Сколько чисел вытягивает автомат
     */
    public const DRAW = 20;

    /**
     * Сколько чисел разрешено отметить
     */
    public const MIN_PICKS = 2;
    public const MAX_PICKS = 10;

    /**
     * Выплаты: сколько отмечено => сколько угадано => множитель к ставке
     *
     * Таблица подобрана под возврат около 95% в каждой строке. Вероятность
     * ровно k совпадений считается гипергеометрическим распределением,
     * точный возврат проверяет тест, поэтому править числа на глаз нельзя.
     *
     * Выплата начинается с частых совпадений и возвращает ставку: партия,
     * в которой не происходит вообще ничего, быстро отбивает охоту играть
     */
    public const PAYOUTS = [
        2  => [1 => 1, 2 => 9.5],
        3  => [1 => 1, 2 => 2.5, 3 => 12.5],
        4  => [2 => 1, 3 => 12, 4 => 70],
        5  => [2 => 1, 3 => 2, 4 => 25, 5 => 320],
        6  => [2 => 1, 3 => 1, 4 => 5, 5 => 80, 6 => 950],
        7  => [2 => 1, 3 => 1, 4 => 2, 5 => 15, 6 => 250, 7 => 1250],
        8  => [3 => 1, 4 => 1, 5 => 6, 6 => 60, 7 => 1500, 8 => 35000],
        9  => [3 => 1, 4 => 1, 5 => 2, 6 => 20, 7 => 250, 8 => 6000, 9 => 100000],
        10 => [3 => 1, 4 => 1, 5 => 1, 6 => 6, 7 => 60, 8 => 800, 9 => 32000, 10 => 100000],
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
        return view('game::keno/index', [
            'user'   => $this->user,
            'game'   => $request->session()->get('keno'),
            'limits' => $this->limits(),
        ]);
    }

    /**
     * Правила игры
     */
    public function rules(): View
    {
        $chances = [];
        foreach (self::PAYOUTS as $picked => $table) {
            $chances[$picked] = $this->frequency($picked);
        }

        return view('game::keno/rules', [
            'payouts' => self::PAYOUTS,
            'chances' => $chances,
            'limits'  => $this->limits(),
        ]);
    }

    /**
     * Размеры поля и границы отметок для шаблонов
     */
    private function limits(): array
    {
        return [
            'field' => self::FIELD,
            'draw'  => self::DRAW,
            'min'   => self::MIN_PICKS,
            'max'   => self::MAX_PICKS,
        ];
    }

    /**
     * Тираж
     */
    public function play(Request $request, Validator $validator): RedirectResponse
    {
        $bet = int($request->input('bet'));
        $picks = $this->picks($request);

        $validator
            ->gt($bet, 0, ['bet' => __('game::games.bj_bet_required')])
            ->gte($this->user->money, $bet, ['bet' => __('game::games.not_enough_money')])
            ->between(count($picks), self::MIN_PICKS, self::MAX_PICKS, ['numbers' => __('game::games.keno_picks_invalid', [
                'min' => self::MIN_PICKS,
                'max' => self::MAX_PICKS,
            ])]);

        if (! $validator->isValid()) {
            return redirect('games/keno')
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        $before = $this->user->money;
        $drawn = $this->draw();
        $matched = array_values(array_intersect($picks, $drawn));
        $win = $this->payout($bet, count($picks), count($matched));

        $this->user->decrement('money', $bet);

        if ($win) {
            $this->user->increment('money', $win);
        }

        return redirect('games/keno')
            ->withInput()
            ->with('keno', [
                'before'  => $before,
                'bet'     => $bet,
                'picks'   => $picks,
                'drawn'   => $drawn,
                'matched' => $matched,
                'win'     => $win,
            ]);
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
     * Доля партий, в которых строка таблицы хоть что-то платит
     */
    public function frequency(int $picked): float
    {
        $chance = 0.0;

        foreach (self::PAYOUTS[$picked] ?? [] as $matched => $multiplier) {
            $chance += $this->chance($picked, $matched);
        }

        return $chance;
    }

    /**
     * Вероятность ровно $matched совпадений при $picked отмеченных числах
     *
     * Гипергеометрическое распределение: из поля тянут DRAW чисел без
     * возврата, поэтому биномиальное здесь дало бы завышенный хвост
     */
    public function chance(int $picked, int $matched): float
    {
        return $this->binomial($picked, $matched)
            * $this->binomial(self::FIELD - $picked, self::DRAW - $matched)
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
    private function payout(int $bet, int $picked, int $matched): int
    {
        $multiplier = self::PAYOUTS[$picked][$matched] ?? 0;

        return (int) ($bet * $multiplier);
    }
}
