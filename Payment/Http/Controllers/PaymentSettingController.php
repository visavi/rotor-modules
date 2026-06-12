<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Models\Setting;
use Illuminate\View\View;
use Modules\Payment\Models\PaidAdvert;

class PaymentSettingController extends ModuleSettingController
{
    protected string $view = 'payment::admin/settings';

    protected string $route = 'payment.settings';

    /**
     * Настройки
     */
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'name')->all();
        $places = (new PaidAdvert())->getPlaces();

        return view($this->view, compact('settings', 'places'));
    }
}
