<?php

declare(strict_types=1);

namespace Modules\MailNotify\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    /**
     * Отписка от писем по ключу из письма
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // ?key[]= приходит массивом: string() на нём падал с 500
        $key = $request->input('key');

        if (! is_string($key) || $key === '') {
            abort(404, __('mail_notify::mail_notify.token_missing'));
        }

        $user = User::query()->where('subscribe', $key)->first();

        if (! $user) {
            abort(404, __('mail_notify::mail_notify.token_expired'));
        }

        $user->update(['subscribe' => null]);

        return redirect('/')
            ->with('success', __('mail_notify::mail_notify.success_unsubscribed'));
    }
}
