<?php

declare(strict_types=1);

namespace Modules\Surprise\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Surprise\Models\Surprise;

class SurpriseController extends Controller
{
    public function index(): RedirectResponse
    {
        $money = mt_rand(10000, 50000);
        $point = mt_rand(150, 250);
        $rating = mt_rand(3, 10);
        $year = date('Y', strtotime('+3 days', SITETIME));

        if (! $user = getUser()) {
            abort(403, __('main.not_authorized'));
        }

        if (strtotime(date('d.m.Y')) > strtotime('03.01' . '.' . $year)) {
            abort(200, __('surprise::surprise.date_receipt'));
        }

        $existSurprise = Surprise::query()
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->first();

        if ($existSurprise) {
            abort(200, __('surprise::surprise.already_received'));
        }

        if ($user->point >= 50) {
            $user->increment('point', $point);
        } else {
            $point = 0;
        }

        $user->increment('money', $money);
        $user->increment('posrating', $rating);
        $user->update(['rating' => $user->posrating - $user->negrating]);

        $text = __('surprise::surprise.notice_text', [
            'year'   => $year,
            'point'  => plural($point, setting('scorename')),
            'money'  => plural($money, setting('moneyname')),
            'rating' => $rating,
        ]);

        $user->sendMessage(null, $text);

        setFlash('success', __('surprise::surprise.success_received'));

        return redirect('/');
    }
}
