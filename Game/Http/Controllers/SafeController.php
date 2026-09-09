<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SafeController extends Controller
{
    /**
     * Цена пяти попыток и награда за вскрытый сейф
     */
    private const PRICE = 100;
    private const PRIZE = 500;

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
    public function index(): View
    {
        return view('game::safe/index', [
            'user'  => $this->user,
            'price' => self::PRICE,
            'prize' => self::PRIZE,
        ]);
    }

    /**
     * Игра
     */
    public function go(Request $request, Validator $validator): View|RedirectResponse
    {
        $code0 = int($request->input('code0'));
        $code1 = int($request->input('code1'));
        $code2 = int($request->input('code2'));
        $code3 = int($request->input('code3'));
        $code4 = int($request->input('code4'));

        $validator->gte($this->user->money, self::PRICE, ['guess' => __('game::games.not_enough_money')]);

        if (! $validator->isValid()) {
            return redirect('games/safe')
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        if ($request->session()->missing('safe.cipher')) {
            $request->session()->put('safe.cipher', [mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9)]);
            $request->session()->put('safe.try', 5);
            $this->user->decrement('money', self::PRICE);
        }

        $request->session()->decrement('safe.try');

        $safe = $request->session()->get('safe');

        $cipher = $safe['cipher'];
        $codes = [$code0, $code1, $code2, $code3, $code4];

        $hack = ['-', '-', '-', '-', '-'];

        // Метки идут тремя проходами, каждый следующий перебивает предыдущий:
        // сначала «такая цифра есть», затем «на этом месте стоит ваша цифра»,
        // и поверх всего — точное попадание
        foreach ($codes as $position => $code) {
            foreach ($cipher as $index => $digit) {
                if ($position !== $index && $code === $digit) {
                    $hack[$position] = '*';
                }
            }
        }

        foreach ($codes as $position => $code) {
            foreach ($cipher as $index => $digit) {
                if ($position !== $index && $code === $digit) {
                    $hack[$index] = 'x';
                }
            }
        }

        foreach ($cipher as $index => $digit) {
            if ($codes[$index] === $digit) {
                $hack[$index] = $digit;
            }
        }

        if (implode($safe['cipher']) === implode($hack)) {
            $request->session()->forget('safe');
            $this->user->increment('money', self::PRIZE);
        }

        if (empty($request->session()->get('safe.try'))) {
            $request->session()->forget('safe');
        }

        $user = $this->user;

        return view('game::safe/go', compact('hack', 'safe', 'user') + ['prize' => self::PRIZE]);
    }
}
