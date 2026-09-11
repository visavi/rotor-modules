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

class SafeController extends Controller
{
    use RejectsInvalidInput;

    /**
     * Цена пяти попыток и награда за вскрытый сейф
     */
    public const PRICE = 100;
    public const PRIZE = 250;

    /**
     * Длина шифра и число попыток
     */
    public const LENGTH = 5;
    public const TRIES = 5;

    /**
     * Метки подсказки
     */
    public const ABSENT = '-';
    public const MOVED = '*';

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
     * Взлом сейфа
     */
    public function index(Request $request): View
    {
        return view('game::safe/index', $this->data($request));
    }

    /**
     * Попытка взлома
     */
    public function go(Request $request, Validator $validator): View|RedirectResponse|JsonResponse
    {
        $codes = $this->codes($request);
        $fresh = $request->session()->missing('safe.cipher');

        $validator->true(count($codes) === self::LENGTH, ['code' => __('game::games.safe_code_invalid')]);

        if ($fresh) {
            $validator->gte($this->user->money, self::PRICE, ['code' => __('game::games.not_enough_money')]);
        }

        if (! $validator->isValid()) {
            if ($answer = $this->ajaxError($request, $validator)) {
                return $answer;
            }

            return redirect('games/safe')
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        $before = $this->user->money;

        if ($fresh) {
            $request->session()->put('safe', [
                'cipher'  => $this->cipher(),
                'try'     => self::TRIES,
                'history' => [],
            ]);

            $this->user->decrement('money', self::PRICE);
        }

        $safe = $request->session()->get('safe');
        $cipher = $safe['cipher'];
        $marks = $this->marks($cipher, $codes);

        $safe['try']--;
        $safe['history'][] = ['codes' => $codes, 'marks' => $marks];

        $opened = $codes === $cipher;

        if ($opened) {
            $this->user->increment('money', self::PRIZE);
        }

        // Партия заканчивается вскрытием или последней попыткой
        if ($opened || $safe['try'] < 1) {
            $request->session()->forget('safe');
        } else {
            $request->session()->put('safe', $safe);
        }

        $game = [
            'before'  => $before,
            'cipher'  => $cipher,
            'try'     => $safe['try'],
            'history' => $safe['history'],
            'marks'   => $marks,
            'result'  => $opened ? 'opened' : ($safe['try'] < 1 ? 'failed' : null),
        ];

        $data = $this->data($request, $game);

        // Попытка уходит ajax-ом: страница не перезагружается, анимация не рвётся
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html'    => view('game::safe/_safe', $data)->render(),
            ]);
        }

        return view('game::safe/index', $data);
    }

    /**
     * Данные для шаблона
     */
    private function data(Request $request, ?array $game = null): array
    {
        $safe = $request->session()->get('safe');

        // Шифр незаконченной партии в шаблон не уходит: подсмотреть его нельзя
        $game ??= $safe ? [
            'before'  => $this->user->money,
            'cipher'  => null,
            'try'     => $safe['try'],
            'history' => $safe['history'],
            'marks'   => null,
            'result'  => null,
        ] : null;

        return [
            'user'  => $this->user,
            'game'  => $game,
            'price' => self::PRICE,
            'prize' => self::PRIZE,
        ];
    }

    /**
     * Присланный код
     */
    private function codes(Request $request): array
    {
        $codes = [];

        foreach (range(0, self::LENGTH - 1) as $position) {
            $digit = $request->input('code' . $position);

            // Цифра и только цифра: int() из «a» сделал бы ноль
            if (is_scalar($digit) && preg_match('/^\d$/', (string) $digit)) {
                $codes[] = (int) $digit;
            }
        }

        return $codes;
    }

    /**
     * Новый шифр
     */
    private function cipher(): array
    {
        $cipher = [];

        foreach (range(1, self::LENGTH) as $ignored) {
            $cipher[] = random_int(0, 9);
        }

        return $cipher;
    }

    /**
     * Подсказка по попытке
     *
     * Цифра на своём месте открывается, цифра из шифра не на своём месте
     * помечается звёздочкой, чужая — прочерком. Позиция чужой цифры не
     * выдаётся: иначе шифр вскрывался бы за две попытки
     */
    private function marks(array $cipher, array $codes): array
    {
        $marks = [];

        foreach ($codes as $position => $code) {
            $marks[] = match (true) {
                $code === $cipher[$position]   => (string) $code,
                in_array($code, $cipher, true) => self::MOVED,
                default                        => self::ABSENT,
            };
        }

        return $marks;
    }
}
