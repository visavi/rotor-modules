<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Validator;
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

        return view('gift::index', compact('gifts', 'user'));
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

        $gift = Gift::query()->find($id);

        if (! $gift) {
            abort(404, __('gift::gifts.gift_not_found'));
        }

        $maxUsers = (int) Gift::getConfig('max_users');

        if ($request->isMethod('post')) {
            $msg = $request->input('msg');
            $logins = array_filter((array) $request->input('users', []));
            $users = User::query()->whereIn('login', $logins)->get();
            $total = $gift->price * $users->count();

            $validator
                ->notEmpty($logins, ['users' => __('validator.user')])
                ->equal($users->count(), count($logins), ['users' => __('gift::gifts.users_not_found')])
                ->lte($users->count(), $maxUsers, ['users' => __('gift::gifts.max_users', ['max' => $maxUsers])])
                ->length($msg, 0, 1000, ['msg' => __('validator.text_long')])
                ->gte(getUser('money'), $total, __('gift::gifts.money_not_enough'));

            if ($validator->isValid()) {
                GiftsUser::query()->where('deleted_at', '<', now())->delete();

                $msg = antimat($msg);

                DB::transaction(static function () use ($gift, $users, $msg, $total) {
                    getUser()->decrement('money', $total);

                    foreach ($users as $user) {
                        GiftsUser::query()->create([
                            'gift_id'      => $gift->id,
                            'user_id'      => $user->id,
                            'send_user_id' => getUser('id'),
                            'text'         => $msg,
                            'deleted_at'   => now()->addDays((int) Gift::getConfig('gift_days')),
                        ]);
                    }
                });

                foreach ($users as $user) {
                    $user->sendMessage(null, textNotice('gift_send', [
                        'login' => getUser('login'),
                        'gift'  => $gift->getImage(),
                        'text'  => (string) $msg,
                        'url'   => '/gifts/' . $user->login,
                        'title' => __('gift::gifts.my_gifts'),
                    ]));
                }

                return redirect('gifts')
                    ->with('success', __('gift::gifts.gift_sent'));
            }

            return redirect('gifts/send/' . $gift->id)
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        $user = getUserByLogin($request->input('user'));

        return view('gift::send', compact('gift', 'user', 'maxUsers'));
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
            ->where('deleted_at', '>', now())
            ->orderByDesc('created_at')
            ->with('gift', 'user', 'sendUser')
            ->get();

        return view('gift::gifts', compact('gifts', 'user'));
    }
}
