<?php

declare(strict_types=1);

namespace Modules\Transfer\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Transfer\Models\Transfer;

/**
 * Перевод денег между пользователями — общий для сайта и API
 */
class TransferService
{
    /**
     * Переводит деньги и уведомляет получателя
     *
     * Баланс проверяется ещё раз при списании: два одновременных перевода
     * проходят проверку запроса оба, и без условия баланс ушёл бы в минус
     *
     * @throws ValidationException
     */
    public function send(User $sender, User $recipient, int $money, string $comment): void
    {
        DB::transaction(static function () use ($sender, $recipient, $money, $comment) {
            if (! $sender->chargeMoney($money)) {
                throw ValidationException::withMessages(['money' => __('transfer::transfers.transfer_not_money')]);
            }

            $recipient->increment('money', $money);

            $recipient->sendMessage(null, textNotice('transfer', [
                'login'   => $sender->login,
                'money'   => plural($money, setting('moneyname')),
                'comment' => $comment,
            ]));

            Transfer::query()->create([
                'user_id'      => $sender->id,
                'recipient_id' => $recipient->id,
                'text'         => $comment,
                'total'        => $money,
            ]);
        });
    }
}
