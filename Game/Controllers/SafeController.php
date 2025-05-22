<?php

declare(strict_types=1);

namespace Modules\Game\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SafeController extends Controller
{
    /**
     * @var User
     */
    private $user;

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
        return view('game::safe/index', ['user' => $this->user]);
    }

    /**
     * Игра
     *
     *
     * @return View|RedirectResponse
     */
    public function go(Request $request, Validator $validator)
    {
        $code0 = int($request->input('code0'));
        $code1 = int($request->input('code1'));
        $code2 = int($request->input('code2'));
        $code3 = int($request->input('code3'));
        $code4 = int($request->input('code4'));

        $validator->equal($request->input('_token'), csrf_token(), __('validator.token'))
            ->gte($this->user->money, 100, ['guess' => 'У вас недостаточно денег для игры!']);

        if (! $validator->isValid()) {
            setInput($request->all());
            setFlash('danger', $validator->getErrors());

            return redirect('games/safe');
        }

        if ($request->session()->missing('safe.cipher')) {
            $request->session()->put('safe.cipher', [mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9)]);
            $request->session()->put('safe.try', 5);
            $this->user->decrement('money', 100);
        }

        $request->session()->decrement('safe.try');

        $safe = $request->session()->get('safe');

        $hack = ['-', '-', '-', '-', '-'];

        if ($code0 === $safe['cipher'][1] || $code0 === $safe['cipher'][2] || $code0 === $safe['cipher'][3] || $code0 === $safe['cipher'][4]) {
            $hack[0] = '*';
        }
        if ($code1 === $safe['cipher'][0] || $code1 === $safe['cipher'][2] || $code1 === $safe['cipher'][3] || $code1 === $safe['cipher'][4]) {
            $hack[1] = '*';
        }
        if ($code2 === $safe['cipher'][0] || $code2 === $safe['cipher'][1] || $code2 === $safe['cipher'][3] || $code2 === $safe['cipher'][4]) {
            $hack[2] = '*';
        }
        if ($code3 === $safe['cipher'][0] || $code3 === $safe['cipher'][1] || $code3 === $safe['cipher'][2] || $code3 === $safe['cipher'][4]) {
            $hack[3] = '*';
        }
        if ($code4 === $safe['cipher'][0] || $code4 === $safe['cipher'][1] || $code4 === $safe['cipher'][2] || $code3 === $safe['cipher'][3]) {
            $hack[3] = '*';
        }

        if ($code0 === $safe['cipher'][1]) {
            $hack[1] = 'x';
        }
        if ($code0 === $safe['cipher'][2]) {
            $hack[2] = 'x';
        }
        if ($code0 === $safe['cipher'][3]) {
            $hack[3] = 'x';
        }
        if ($code0 === $safe['cipher'][4]) {
            $hack[4] = 'x';
        }

        if ($code1 === $safe['cipher'][0]) {
            $hack[0] = 'x';
        }
        if ($code1 === $safe['cipher'][2]) {
            $hack[2] = 'x';
        }
        if ($code1 === $safe['cipher'][3]) {
            $hack[3] = 'x';
        }
        if ($code1 === $safe['cipher'][4]) {
            $hack[4] = 'x';
        }

        if ($code2 === $safe['cipher'][0]) {
            $hack[0] = 'x';
        }
        if ($code2 === $safe['cipher'][1]) {
            $hack[1] = 'x';
        }
        if ($code2 === $safe['cipher'][3]) {
            $hack[3] = 'x';
        }
        if ($code2 === $safe['cipher'][4]) {
            $hack[4] = 'x';
        }

        if ($code3 === $safe['cipher'][0]) {
            $hack[0] = 'x';
        }
        if ($code3 === $safe['cipher'][1]) {
            $hack[1] = 'x';
        }
        if ($code3 === $safe['cipher'][2]) {
            $hack[2] = 'x';
        }
        if ($code3 === $safe['cipher'][4]) {
            $hack[4] = 'x';
        }

        if ($code4 === $safe['cipher'][0]) {
            $hack[0] = 'x';
        }
        if ($code4 === $safe['cipher'][1]) {
            $hack[1] = 'x';
        }
        if ($code4 === $safe['cipher'][2]) {
            $hack[2] = 'x';
        }
        if ($code4 === $safe['cipher'][3]) {
            $hack[3] = 'x';
        }

        if ($code0 === $safe['cipher'][0]) {
            $hack[0] = $safe['cipher'][0];
        }
        if ($code1 === $safe['cipher'][1]) {
            $hack[1] = $safe['cipher'][1];
        }
        if ($code2 === $safe['cipher'][2]) {
            $hack[2] = $safe['cipher'][2];
        }
        if ($code3 === $safe['cipher'][3]) {
            $hack[3] = $safe['cipher'][3];
        }
        if ($code4 === $safe['cipher'][4]) {
            $hack[4] = $safe['cipher'][4];
        }

        if (implode($safe['cipher']) === implode($hack)) {
            $request->session()->forget('safe');
            $this->user->increment('money', 1000);
        }

        if (empty($request->session()->get('safe.try'))) {
            $request->session()->forget('safe');
        }

        $user = $this->user;

        return view('game::safe/go', compact('hack', 'safe', 'user'));
    }
}
