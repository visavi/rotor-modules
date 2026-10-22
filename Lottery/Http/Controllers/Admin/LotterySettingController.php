<?php

declare(strict_types=1);

namespace Modules\Lottery\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class LotterySettingController extends ModuleSettingController
{
    /**
     * Номер хранится в smallInteger
     */
    private const int MAX_NUMBER = 32767;

    /**
     * Банк хранится в integer
     */
    private const int MAX_AMOUNT = 2147483647;

    protected string $view = 'lottery::admin/settings/_lottery';

    protected string $route = 'lottery.settings';

    protected function rules(): array
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

    protected function messages(): array
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
