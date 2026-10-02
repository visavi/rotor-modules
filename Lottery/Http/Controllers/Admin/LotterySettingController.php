<?php

declare(strict_types=1);

namespace Modules\Lottery\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    /**
     * Сохранение настроек
     *
     * Диапазон проверяется заранее: при минимуме больше максимума
     * random_int в розыгрыше падает, и тираж не сменится
     */
    public function update(Request $request): RedirectResponse
    {
        $validator = new Validator();
        $sets = (array) $request->input('sets', []);

        $min = (int) ($sets['lottery_min'] ?? 0);
        $max = (int) ($sets['lottery_max'] ?? 0);

        $validator
            ->between((int) ($sets['lottery_jackpot'] ?? -1), 0, self::MAX_AMOUNT, ['sets[lottery_jackpot]' => __('validator.between', ['min' => 0, 'max' => self::MAX_AMOUNT])])
            ->between((int) ($sets['lottery_ticket_price'] ?? -1), 0, self::MAX_AMOUNT, ['sets[lottery_ticket_price]' => __('validator.between', ['min' => 0, 'max' => self::MAX_AMOUNT])])
            ->between($min, 0, self::MAX_NUMBER, ['sets[lottery_min]' => __('validator.between', ['min' => 0, 'max' => self::MAX_NUMBER])])
            ->between($max, 0, self::MAX_NUMBER, ['sets[lottery_max]' => __('validator.between', ['min' => 0, 'max' => self::MAX_NUMBER])])
            ->lt($min, $max, ['sets[lottery_max]' => __('lottery::lottery.range_invalid')]);

        if ($validator->fails()) {
            return redirect()->route($this->route)
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        return parent::update($request);
    }
}
