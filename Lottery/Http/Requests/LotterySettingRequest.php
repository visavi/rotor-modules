<?php

declare(strict_types=1);

namespace Modules\Lottery\Http\Requests;

use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Настройки лотереи
 *
 * Диапазон проверяется заранее: при минимуме больше максимума
 * random_int в розыгрыше падает, и тираж не сменится
 */
#[RedirectToRoute('lottery.settings')]
class LotterySettingRequest extends FormRequest
{
    /**
     * Номер хранится в smallInteger
     */
    private const int MAX_NUMBER = 32767;

    /**
     * Банк хранится в integer
     */
    private const int MAX_AMOUNT = 2147483647;

    public function rules(): array
    {
        $amount = ['required', 'integer', 'between:0,' . self::MAX_AMOUNT];
        $number = ['required', 'integer', 'between:0,' . self::MAX_NUMBER];

        return [
            'sets.lottery_jackpot'      => $amount,
            'sets.lottery_ticket_price' => $amount,
            'sets.lottery_min'          => $number,
            'sets.lottery_max'          => [...$number, 'gt:sets.lottery_min'],
        ];
    }

    public function messages(): array
    {
        $amount = __('validator.between', ['min' => 0, 'max' => self::MAX_AMOUNT]);
        $number = __('validator.between', ['min' => 0, 'max' => self::MAX_NUMBER]);

        return [
            'sets.lottery_jackpot'      => $amount,
            'sets.lottery_ticket_price' => $amount,
            'sets.lottery_min'          => $number,
            'sets.lottery_max.gt'       => __('lottery::lottery.range_invalid'),
            'sets.lottery_max'          => $number,
        ];
    }
}
