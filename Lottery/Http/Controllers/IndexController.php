<?php

declare(strict_types=1);

namespace Modules\Lottery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Lottery\Models\Lottery;
use Modules\Lottery\Services\LotteryService;
use Throwable;

class IndexController extends Controller
{
    /**
     * Main page
     */
    public function index(LotteryService $service): View
    {
        // Подстраховка для сайтов без планировщика: тираж разыгрывается заходом
        $service->draw();

        $lottery = Lottery::query()
            ->orderByDesc('day')
            ->limit(2)
            ->get();

        $lottery = $lottery->pad(2, null);

        [$today, $yesterday] = $lottery;

        // Тираж мог не открыться, если розыгрыш прямо сейчас идёт в соседнем запросе
        if (! $today) {
            abort(200, __('lottery::lottery.lottery_not_activated'));
        }

        if ($yesterday) {
            $yesterday->winners = $yesterday->lotteryUsers()
                ->where('number', $yesterday->number)
                ->get();
        }

        $config = Lottery::getConfig();

        $ticket = $today->lotteryUsers()
            ->where('user_id', getUser('id'))
            ->first();

        return view('lottery::index', compact('today', 'yesterday', 'config', 'ticket'));
    }

    /**
     * Buy ticket
     *
     *
     * @throws Throwable
     */
    public function buy(Request $request, Validator $validator, LotteryService $service): RedirectResponse
    {
        // Без планировщика тираж мог не смениться с прошлых суток
        $service->draw();

        $number = int($request->input('number'));
        $ticketPrice = Lottery::getConfig('ticketPrice');
        $numberRange = Lottery::getConfig('numberRange');

        if (! $user = getUser()) {
            abort(403);
        }

        $lottery = Lottery::query()
            ->orderByDesc('day')
            ->first();

        if (! $lottery) {
            abort(200, __('lottery::lottery.lottery_not_activated'));
        }

        $ticketExist = $lottery->lotteryUsers()
            ->where('user_id', $user->id)
            ->exists();

        $validator
            ->false($ticketExist, ['number' => __('lottery::lottery.already_bought_ticket')])
            ->lte($ticketPrice, getUser('money'), ['number' => __('lottery::lottery.no_money')])
            ->between($number, $numberRange[0], $numberRange[1], ['number' => __('lottery::lottery.must_enter_number')]);

        if ($validator->isValid()) {
            $bought = DB::transaction(
                static function () use ($user, $number, $lottery, $ticketPrice) {
                    // Блокировка тиража: двойной клик иначе покупал два билета
                    $lottery = Lottery::query()->whereKey($lottery->id)->lockForUpdate()->first();

                    if (! $lottery || $lottery->lotteryUsers()->where('user_id', $user->id)->exists()) {
                        return false;
                    }

                    $user->decrement('money', $ticketPrice);
                    $lottery->increment('amount', $ticketPrice);

                    $lottery->lotteryUsers()->create([
                        'user_id' => $user->id,
                        'number'  => $number,
                    ]);

                    return true;
                }
            );

            if (! $bought) {
                return redirect('lottery')
                    ->withErrors(['number' => __('lottery::lottery.already_bought_ticket')]);
            }

            return redirect('lottery')
                ->with('success', __('lottery::lottery.ticket_success_purchased'));
        }

        return redirect('lottery')
            ->withInput()
            ->withErrors($validator->getErrors());
    }
}
