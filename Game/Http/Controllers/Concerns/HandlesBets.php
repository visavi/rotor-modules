<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers\Concerns;

use App\Models\User;

/**
 * Ставки в играх: правила и сообщения проверки ставки
 */
trait HandlesBets
{
    /**
     * Правила ставки: целое число от единицы до суммы на счёте
     */
    protected function betRules(User $user): array
    {
        return ['required', 'integer', 'min:1', 'max:' . $user->money];
    }

    /**
     * Сообщения правил ставки
     */
    protected function betMessages(): array
    {
        return [
            'bet.max' => __('game::games.not_enough_money'),
            'bet.*'   => __('game::games.bj_bet_required'),
        ];
    }
}
