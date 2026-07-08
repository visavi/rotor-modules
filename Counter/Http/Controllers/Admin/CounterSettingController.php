<?php

declare(strict_types=1);

namespace Modules\Counter\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class CounterSettingController extends ModuleSettingController
{
    protected string $view = 'counter::admin/settings/_counters';

    protected string $route = 'counter.settings';
}
