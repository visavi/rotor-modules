<?php

declare(strict_types=1);

namespace Modules\Game\Http\Controllers\Concerns;

/**
 * Ставки в играх: правила и сообщения проверки ставки
 *
 * Класс, который использует трейт, держит игрока в свойстве $user
 */
trait HandlesBets
{
    /**
     * Правила ставки: целое число от единицы до суммы на счёте
     */
    protected function betRules(): array
    {
        return ['required', 'integer', 'min:1', 'max:' . $this->user->money];
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
