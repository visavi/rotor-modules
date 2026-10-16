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
        $key = $request->string('key')->value();

        if ($key === '') {
            abort(200, __('mail_notify::mail_notify.token_missing'));
        }

        $user = User::query()->where('subscribe', $key)->first();

        if (! $user) {
            abort(200, __('mail_notify::mail_notify.token_expired'));
        }

        $user->update(['subscribe' => null]);

        return redirect('/')
            ->with('success', __('mail_notify::mail_notify.success_unsubscribed'));
    }
}
