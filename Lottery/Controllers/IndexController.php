<?php

declare(strict_types=1);

namespace Modules\Lottery\Controllers;

use App\Classes\Validator;
use App\Controllers\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Http\Request;
use Modules\Lottery\Models\Lottery;
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

        return view('Lottery::index', compact('today', 'yesterday', 'config'));
    }

    /**
     * Buy ticket
     *
     * @param  Request  $request
     * @param  Validator  $validator
     *
     * @throws Throwable
     */
    public function buy(Request $request, Validator $validator): void
    {
        $number      = check($request->input('number'));
        $token       = check($request->input('token'));
        $ticketPrice = Lottery::getConfig('ticketPrice');
        $numberRange = Lottery::getConfig('numberRange');

        if (! $user = getUser()) {
            abort(403);
        }

        $lottery = Lottery::query()
            ->orderByDesc('day')
            ->first();

        if (!$lottery) {
            abort('default', __('Lottery::lottery.lottery_not_activated'));
        }

        $lotteryUser = $lottery->lotteryUsers()
            ->where('user_id', $user->id)
            ->first();

        $validator
            ->equal($token, $_SESSION['token'], ['number' => __('validator.token')])
            ->empty($lotteryUser, ['number' => __('Lottery::lottery.already_bought_ticket')])
            ->lte($ticketPrice, getUser('money'), ['number' => __('Lottery::lottery.no_money')])
            ->between($number, $numberRange[0], $numberRange[1], ['number' => __('Lottery::lottery.must_enter_number')]);

        if ($validator->isValid()) {
            DB::connection()->transaction(
                static function () use ($user, $number, $lottery, $ticketPrice) {
                $user->decrement('money', $ticketPrice);
                $lottery->increment('amount', $ticketPrice);

                $lottery->lotteryUsers()->create([
                    'user_id'    => $user->id,
                    'number'     => $number,
                    'created_at' => SITETIME
                ]);
            });

            setFlash('success', __('Lottery::lottery.ticket_success_purchased'));
        } else {
            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        redirect('/lottery');
    }

    /**
     * Reward winners
     */
    private function rewardWinners(): void
    {
        $amount = Lottery::getConfig('jackpot');
        $range  = Lottery::getConfig('numberRange');

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

                $message = __('Lottery::lottery.congratulations_winning', ['jackpot' => plural($moneys, setting('moneyname'))]);

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
                'day'     => date('Y-m-d', SITETIME),
                'amount'  => $amount,
                'number'  => mt_rand($range[0], $range[1]),
            ]);
        }
    }
}
