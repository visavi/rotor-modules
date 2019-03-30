<?php

declare(strict_types=1);

namespace App\Modules\Gift\Controllers;

use App\Classes\Validator;
use App\Controllers\BaseController;
use App\Modules\Gift\Models\Gift;
use App\Modules\Gift\Models\GiftsUser;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Http\Request;

class IndexController extends BaseController
{
    /**
     * Главная страница
     *
     * @return string
     */
    public function index(): string
    {

        $total = Gift::query()->count();
        $page  = paginate(100, $total);

        $gifts = Gift::query()
            ->orderBy('price')
            ->limit($page->limit)
            ->offset($page->offset)
            ->get();

        return view('Gift::index', compact('gifts', 'page'));
    }

    /**
     * Отправка подарка
     *
     * @param int       $id
     * @param Request   $request
     * @param Validator $validator
     * @return string
     * @throws \Throwable
     */
    public function send(int $id, Request $request, Validator $validator): string
    {
        $login = check($request->input('user'));

        /** @var Gift $gift */
        $gift = Gift::query()->find($id);

        if (! $gift) {
            abort(404, 'Данный подарок не найден!');
        }

        $user = getUserByLogin($login);

        if ($request->isMethod('post')) {
            $token = check($request->input('token'));
            $msg   = check($request->input('msg'));

            $validator->equal($token, $_SESSION['token'], ['msg' => trans('validator.token')])
                ->notEmpty($user, ['user' => trans('validator.user')])
                ->length($msg, 0, 1000, ['msg' => 'Слишком длинный текст!'])
                ->gte(getUser('money'), $gift->price, 'У вас недостаточно денег для подарка!');

            if ($validator->isValid()) {
                $msg = antimat($msg);

                DB::connection()->transaction(function () use ($gift, $user, $msg) {
                    getUser()->decrement('money', $gift->price);

                    GiftsUser::query()->create([
                        'gift_id'      => $gift->id,
                        'user_id'      => $user->id,
                        'send_user_id' => getUser('id'),
                        'text'         => $msg,
                        'created_at'   => SITETIME,
                        'deleted_at'   => strtotime('+1 month', SITETIME)
                    ]);
                });

                setFlash('success', 'Подарок успешно отправлен!');
                redirect('/gifts');
            } else {
                setInput($request->all());
                setFlash('danger', $validator->getErrors());
            }
        }

        return view('Gift::send', compact('gift', 'user'));
    }

    /**
     * Просмотр подарков
     *
     * @param string $login
     * @return string
     */
    public function gifts(string $login): string
    {
        $user = getUserByLogin($login);

        if (! $user) {
            abort(404, trans('validator.user'));
        }

        $gifts = GiftsUser::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->with('gift', 'sendUser')
            ->get();

        return view('Gift::gifts', compact('gifts', 'user'));
    }
}
