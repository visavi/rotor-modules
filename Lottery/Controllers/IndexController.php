<?php

declare(strict_types=1);

namespace Modules\Lottery\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Lottery\Models\Lottery;
use Throwable;

class IndexController extends Controller
{
    /**
     * Main page
     */
    public function index(): View
    {
        $this->rewardWinners();

        $lottery = Lottery::query()
            ->orderByDesc('day')
            ->limit(2)
            ->get();

        $lottery = $lottery->pad(2, null);

        [$today, $yesterday] = $lottery;

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
    public function buy(Request $request, Validator $validator): RedirectResponse
    {
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
            ->equal($request->input('_token'), csrf_token(), ['number' => __('validator.token')])
            ->false($ticketExist, ['number' => __('lottery::lottery.already_bought_ticket')])
            ->lte($ticketPrice, getUser('money'), ['number' => __('lottery::lottery.no_money')])
            ->between($number, $numberRange[0], $numberRange[1], ['number' => __('lottery::lottery.must_enter_number')]);

        if ($validator->isValid()) {
            DB::transaction(
                static function () use ($user, $number, $lottery, $ticketPrice) {
                    $user->decrement('money', $ticketPrice);
                    $lottery->increment('amount', $ticketPrice);

                    $lottery->lotteryUsers()->create([
                        'user_id'    => $user->id,
                        'number'     => $number,
                        'created_at' => SITETIME,
                    ]);
                }
            );

            setFlash('success', __('lottery::lottery.ticket_success_purchased'));
        } else {
            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        return redirect('lottery');
    }

    /**
     * Reward winners
     */
    private function rewardWinners(): void
    {
        $amount = Lottery::getConfig('jackpot');
        $range = Lottery::getConfig('numberRange');

        $lottery = Lottery::query()
            ->orderByDesc('day')
            ->first();

        if ($lottery && $lottery->day !== date('Y-m-d', SITETIME)) {
            // Search winners
            $winners = $lottery->lotteryUsers()
                ->where('number', $lottery->number)
                ->get();

            if ($winners->isNotEmpty()) {
                $moneys = (int) ($lottery->amount / $winners->count());

                $message = __('lottery::lottery.congratulations_winning', ['jackpot' => plural($moneys, setting('moneyname'))]);

                foreach ($winners as $winner) {
                    $winner->user->increment('money', $moneys);
                    $winner->user->sendMessage(null, $message);
                }
            } else {
                $amount = $lottery->amount;
            }
        }

        if (! $lottery || $lottery->day !== date('Y-m-d', SITETIME)) {
            // Update lottery
            Lottery::query()->create([
                'day'    => date('Y-m-d', SITETIME),
                'amount' => $amount,
                'number' => mt_rand($range[0], $range[1]),
            ]);
        }
    }
}
