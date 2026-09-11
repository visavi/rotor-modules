<?php

declare(strict_types=1);

namespace Modules\Lottery\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Lottery\Models\Lottery;

class LotteryService
{
    /**
     * Сколько секунд держится блокировка розыгрыша
     */
    private const LOCK_SECONDS = 10;

    /**
     * Разыгрывает вчерашний тираж и открывает сегодняшний
     *
     * Вызывается и планировщиком, и заходом на страницу: планировщик включен
     * не у всех, поэтому ленивый вызов остаётся рабочим механизмом. Оба пути
     * проходят через блокировку, иначе два одновременных запроса разыграли бы
     * банк дважды и создали два тиража на один день
     */
    public function draw(): bool
    {
        $lock = Cache::lock('lottery.draw', self::LOCK_SECONDS);

        if (! $lock->get()) {
            return false;
        }

        try {
            return DB::transaction(fn () => $this->play());
        } finally {
            $lock->release();
        }
    }

    /**
     * Розыгрыш под блокировкой
     */
    private function play(): bool
    {
        $today = now()->format('Y-m-d');

        $lottery = Lottery::query()
            ->orderByDesc('day')
            ->lockForUpdate()
            ->first();

        if ($lottery && $lottery->day === $today) {
            return false;
        }

        $amount = (int) Lottery::getConfig('jackpot');

        if ($lottery) {
            $amount = $this->reward($lottery) ? $amount : $lottery->amount;
        }

        Lottery::query()->create([
            'day'    => $today,
            'amount' => $amount,
            'number' => null,
        ]);

        return true;
    }

    /**
     * Разыгрывает номер тиража и раздаёт банк победителям
     *
     * Номер тянется в момент розыгрыша: пока тираж идёт, его не знает никто,
     * в том числе тот, у кого есть доступ к базе
     */
    private function reward(Lottery $lottery): bool
    {
        [$min, $max] = Lottery::getConfig('numberRange');

        $number = random_int((int) $min, (int) $max);

        $lottery->update(['number' => $number]);

        $winners = $lottery->lotteryUsers()
            ->where('number', $number)
            ->get();

        if ($winners->isEmpty()) {
            return false;
        }

        $money = intdiv($lottery->amount, $winners->count());
        $message = __('lottery::lottery.congratulations_winning', [
            'jackpot' => plural($money, setting('moneyname')),
        ]);

        foreach ($winners as $winner) {
            $winner->user->increment('money', $money);
            $winner->user->sendMessage(null, $message);
        }

        return true;
    }
}
