<?php

declare(strict_types=1);

namespace Modules\Gift\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;

/**
 * Отправка подарков — общая для сайта и API
 */
class GiftService
{
    /**
     * Дарит подарок каждому получателю и уведомляет их
     *
     * Баланс проверяется ещё раз при списании: два одновременных запроса
     * проходят проверку оба, и без условия баланс ушёл бы в минус
     *
     * @param Collection<int, User> $recipients
     *
     * @throws ValidationException
     */
    public function send(User $sender, Gift $gift, Collection $recipients, string $text): void
    {
        $total = $gift->price * $recipients->count();

        // Заодно подчищаем просроченные
        GiftsUser::query()->where('deleted_at', '<', now())->delete();

        DB::transaction(static function () use ($sender, $gift, $recipients, $text, $total) {
            if (! $sender->chargeMoney($total)) {
                throw ValidationException::withMessages(['money' => __('gift::gifts.money_not_enough')]);
            }

            foreach ($recipients as $recipient) {
                GiftsUser::query()->create([
                    'gift_id'      => $gift->id,
                    'user_id'      => $recipient->id,
                    'send_user_id' => $sender->id,
                    'text'         => $text,
                    'deleted_at'   => now()->addDays((int) setting('gift_days')),
                ]);
            }
        });

        foreach ($recipients as $recipient) {
            $recipient->sendMessage(null, textNotice('gift_send', [
                'login' => $sender->login,
                'gift'  => $gift->getImage(),
                'text'  => $text,
                'url'   => '/gifts/' . $recipient->login,
                'title' => __('gift::gifts.my_gifts'),
            ]));
        }
    }
}
