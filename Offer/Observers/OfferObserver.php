<?php

declare(strict_types=1);

namespace Modules\Offer\Observers;

use Modules\Offer\Models\Offer;

class OfferObserver
{
    /**
     * Handle the Offer "updated" event.
     *
     * Награда — за выполненное предложение или исправленную проблему: статус ставит администратор.
     * Снятый статус забирает награду, но не больше остатка
     */
    public function updated(Offer $offer): void
    {
        if (! $offer->wasChanged('status')) {
            return;
        }

        $wasDone = $offer->getOriginal('status') === Offer::DONE;
        $isDone = $offer->status === Offer::DONE;

        if ($wasDone === $isDone) {
            return;
        }

        $user = $offer->user;
        // Настройки может не быть — модуль ставят раньше, чем задают его настройки
        $point = (int) setting('offer_point');
        $money = (int) setting('offer_money');

        if ($isDone) {
            $user->increment('point', $point);
            $user->increment('money', $money);
        } else {
            $user->decrement('point', min($point, $user->point));
            $user->decrement('money', min($money, $user->money));
        }
    }
}
