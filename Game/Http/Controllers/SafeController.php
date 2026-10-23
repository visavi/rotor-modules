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

class SafeController extends Controller
{
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
     * Взлом сейфа
     */
    public function index(Request $request, #[CurrentUser] User $user): View
    {
        return view('game::safe/index', $this->data($user, $request));
    }

    /**
     * Попытка взлома
     */
    public function go(Request $request, #[CurrentUser] User $user): View|RedirectResponse|JsonResponse
    {
        $codes = $this->codes($request);
        $fresh = $request->session()->missing('safe.cipher');

        if (count($codes) !== self::LENGTH) {
            throw ValidationException::withMessages(['code' => __('game::games.safe_code_invalid')]);
        }

        $before = $user->money;

        // Новая партия оплачивается до начала: без денег она не должна начаться
        if ($fresh && ! $user->chargeMoney(self::PRICE)) {
            throw ValidationException::withMessages(['code' => __('game::games.not_enough_money')]);
        }

        if ($fresh) {
            $request->session()->put('safe', [
                'cipher'  => $this->cipher(),
                'try'     => self::TRIES,
                'history' => [],
            ]);
        }

        $safe = $request->session()->get('safe');
        $cipher = $safe['cipher'];
        $marks = $this->marks($cipher, $codes);

        $safe['try']--;
        $safe['history'][] = ['codes' => $codes, 'marks' => $marks];

        $opened = $codes === $cipher;

        if ($opened) {
            $user->increment('money', self::PRIZE);
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

        $data = $this->data($user, $request, $game);

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
    private function data(User $user, Request $request, ?array $game = null): array
    {
        $safe = $request->session()->get('safe');

        // Шифр незаконченной партии в шаблон не уходит: подсмотреть его нельзя
        $game ??= $safe ? [
            'before'  => $user->money,
            'cipher'  => null,
            'try'     => $safe['try'],
            'history' => $safe['history'],
            'marks'   => null,
            'result'  => null,
        ] : null;

        return [
            'user'  => $user,
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
