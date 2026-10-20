<?php

declare(strict_types=1);

namespace Modules\Lottery\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use Illuminate\Http\RedirectResponse;
use Modules\Lottery\Http\Requests\LotterySettingRequest;

class LotterySettingController extends ModuleSettingController
{
    protected string $view = 'lottery::admin/settings/_lottery';

    protected string $route = 'lottery.settings';

    /**
     * Сохранение настроек: свои правила, затем общее сохранение
     */
    public function store(LotterySettingRequest $request): RedirectResponse
    {
        return parent::update($request);
    }
}
