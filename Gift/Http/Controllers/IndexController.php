<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Gift\Http\Requests\GiftSendRequest;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;
use Modules\Gift\Services\GiftService;

class IndexController extends Controller
{
    /**
     * Main page
     */
    public function index(Request $request): View
    {
        $user = $request->input('user');
        $perPage = (int) setting('gift_per_page');

        $gifts = Gift::query()
            ->orderBy('price')
            ->paginate($perPage);

        if ($user) {
            $gifts->appends(['user' => $user]);
        }

        return view('gift::index', compact('gifts', 'user'));
    }

    /**
     * Gift sending form
     */
    public function send(int $id, Request $request): View
    {
        $gift = Gift::query()->find($id);

        if (! $gift) {
            abort(404, __('gift::gifts.gift_not_found'));
        }

        $maxUsers = (int) setting('gift_max_users');
        $user = getUserByLogin($request->input('user'));

        return view('gift::send', compact('gift', 'user', 'maxUsers'));
    }

    /**
     * Sends a gift
     */
    public function store(GiftSendRequest $request, GiftService $service, #[CurrentUser] User $user): RedirectResponse
    {
        $service->send($user, $request->gift(), $request->recipients(), $request->string('text')->value());

        return redirect('gifts')
            ->with('success', __('gift::gifts.gift_sent'));
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
