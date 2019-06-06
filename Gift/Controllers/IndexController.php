<?php

declare(strict_types=1);

namespace Modules\Gift\Controllers;

use App\Classes\Validator;
use App\Controllers\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Http\Request;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;
use Throwable;

class IndexController extends BaseController
{
    /**
     * Main page
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
     * Sends a gift
     *
     * @param int       $id
     * @param Request   $request
     * @param Validator $validator
     * @return string
     * @throws Throwable
     */
    public function send(int $id, Request $request, Validator $validator): string
    {
        $login = check($request->input('user'));

        /** @var Gift $gift */
        $gift = Gift::query()->find($id);

        if (! $gift) {
            abort(404, trans('Gift::gifts.gift_not_found'));
        }

        $user = getUserByLogin($login);

        if ($request->isMethod('post')) {
            $token = check($request->input('token'));
            $msg   = check($request->input('msg'));

            $validator->equal($token, $_SESSION['token'], ['msg' => trans('validator.token')])
                ->notEmpty($user, ['user' => trans('validator.user')])
                ->length($msg, 0, 1000, ['msg' => trans('validator.text_long')])
                ->gte(getUser('money'), $gift->price, trans('Gift::gifts.money_not_enough'));

            if ($validator->isValid()) {
                GiftsUser::query()->where('deleted_at', '<', SITETIME)->delete();

                $msg = antimat($msg);

                DB::connection()->transaction(static function () use ($gift, $user, $msg) {
                    getUser()->decrement('money', $gift->price);

                    GiftsUser::query()->create([
                        'gift_id'      => $gift->id,
                        'user_id'      => $user->id,
                        'send_user_id' => getUser('id'),
                        'text'         => $msg,
                        'created_at'   => SITETIME,
                        'deleted_at'   => strtotime('+' . GiftsUser::GIFT_DAYS . 'days', SITETIME)
                    ]);
                });

                setFlash('success', trans('Gift::gifts.gift_sent'));
                redirect('/gifts');
            } else {
                setInput($request->all());
                setFlash('danger', $validator->getErrors());
            }
        }

        return view('Gift::send', compact('gift', 'user'));
    }

    /**
     * View gifts
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
            ->where('deleted_at', '>', SITETIME)
            ->orderBy('created_at', 'desc')
            ->with('gift', 'user', 'sendUser')
            ->get();

        return view('Gift::gifts', compact('gifts', 'user'));
    }
}
