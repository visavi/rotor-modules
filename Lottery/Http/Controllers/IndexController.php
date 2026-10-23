<?php

declare(strict_types=1);

namespace Modules\Lottery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Lottery\Http\Requests\TicketRequest;
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
                ->with('user')
                ->get();
        }

        $today->load('lotteryUsers.user');

        $ticketPrice = (int) setting('lottery_ticket_price');
        $min = (int) setting('lottery_min');
        $max = (int) setting('lottery_max');

        $ticket = $today->lotteryUsers->firstWhere('user_id', auth()->id());

        return view('lottery::index', compact('today', 'yesterday', 'ticket', 'ticketPrice', 'min', 'max'));
    }

    /**
     * Buy ticket
     *
     * Баланс проверяется ещё раз при списании: деньги могли уйти в другом
     * разделе после проверки, и без условия баланс ушёл бы в минус
     *
     * @throws Throwable
     */
    public function buy(TicketRequest $request, LotteryService $service, #[CurrentUser] User $user): RedirectResponse
    {
        // Без планировщика тираж мог не смениться с прошлых суток
        $service->draw();

        $number = $request->integer('number');
        $ticketPrice = (int) setting('lottery_ticket_price');

        $lottery = Lottery::query()
            ->orderByDesc('day')
            ->first();

        if (! $lottery) {
            abort(200, __('lottery::lottery.lottery_not_activated'));
        }

        $error = DB::transaction(static function () use ($user, $number, $lottery, $ticketPrice) {
            // Блокировка тиража: двойной клик иначе покупал два билета
            $lottery = Lottery::query()->whereKey($lottery->id)->lockForUpdate()->first();

            if (! $lottery || $lottery->lotteryUsers()->where('user_id', $user->id)->exists()) {
                return __('lottery::lottery.already_bought_ticket');
            }

            if (! $user->chargeMoney($ticketPrice)) {
                return __('lottery::lottery.no_money');
            }

            $lottery->increment('amount', $ticketPrice);

            $lottery->lotteryUsers()->create([
                'user_id' => $user->id,
                'number'  => $number,
            ]);

            return null;
        });

        if ($error) {
            return redirect('lottery')
                ->withInput()
                ->withErrors(['number' => $error]);
        }

        return redirect('lottery')
            ->with('success', __('lottery::lottery.ticket_success_purchased'));
    }
}
