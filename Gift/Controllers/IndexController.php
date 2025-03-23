<?php

declare(strict_types=1);

namespace Modules\Gift\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;
use Throwable;

class IndexController extends Controller
{
    /**
     * Main page
     */
    public function index(Request $request): View
    {
        $user = $request->input('user');
        $perPage = Gift::getConfig('per_page');

        $gifts = Gift::query()
            ->orderBy('price')
            ->paginate($perPage);

        if ($user) {
            $gifts->appends(['user' => $user]);
        }

        return view('Gift::index', compact('gifts', 'user'));
    }

    /**
     * Sends a gift
     *
     * @throws Throwable
     */
    public function send(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        if (! getUser()) {
            abort(403, __('main.not_authorized'));
        }

        /** @var Gift $gift */
        $gift = Gift::query()->find($id);

        if (! $gift) {
            abort(404, __('Gift::gifts.gift_not_found'));
        }

        $user = getUserByLogin($request->input('user'));

        if ($request->isMethod('post')) {
            $msg = $request->input('msg');

            $validator->equal($request->input('_token'), csrf_token(), ['msg' => __('validator.token')])
                ->notEmpty($user, ['user' => __('validator.user')])
                ->length($msg, 0, 1000, ['msg' => __('validator.text_long')])
                ->gte(getUser('money'), $gift->price, __('Gift::gifts.money_not_enough'));

            if ($validator->isValid()) {
                GiftsUser::query()->where('deleted_at', '<', SITETIME)->delete();

                $msg = antimat($msg);

                DB::transaction(static function () use ($gift, $user, $msg) {
                    getUser()->decrement('money', $gift->price);

                    GiftsUser::query()->create([
                        'gift_id'      => $gift->id,
                        'user_id'      => $user->id,
                        'send_user_id' => getUser('id'),
                        'text'         => $msg,
                        'created_at'   => SITETIME,
                        'deleted_at'   => strtotime('+' . Gift::getConfig('gift_days') . 'days', SITETIME),
                    ]);
                });

                $message = 'Пользователь @' . getUser('login') . ' отправил вам подарок!' . PHP_EOL . '[img]' . $gift->path . '[/img] ' . $msg . PHP_EOL . '[url=/gifts/' . $user->login . ']Мои подарки[/url]';
                $user->sendMessage(null, $message);

                setFlash('success', __('Gift::gifts.gift_sent'));

                return redirect('gifts');
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        return view('Gift::send', compact('gift', 'user'));
    }

    /**
     * View gifts
     */
    public function gifts(string $login): View
    {
        $user = getUserByLogin($login);

        if (! $user) {
            abort(404, __('validator.user'));
        }

        $gifts = GiftsUser::query()
            ->where('user_id', $user->id)
            ->where('deleted_at', '>', SITETIME)
            ->orderByDesc('created_at')
            ->with('gift', 'user', 'sendUser')
            ->get();

        return view('Gift::gifts', compact('gifts', 'user'));
    }
}
